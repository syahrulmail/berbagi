<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'trustbar_text' => Setting::get('trustbar_text', 'Badan Wakaf Al Qur\'an · Terdaftar & Berizin'),
            'home_quote' => Setting::get('home_quote', '<p>&quot;Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.&quot; — <strong>HR. Ahmad &amp; Thabrani</strong></p>'),
            'home_testimonials' => $this->decodeTestimonials(Setting::get('home_testimonials', '[]')),
            'home_partner_logos' => $this->decodeLogos(Setting::get('home_partner_logos', '[]')),
            'wa_reminder_enabled' => Setting::get('wa_reminder_enabled', '0'),
            'wa_reminder_hour' => Setting::get('wa_reminder_hour', '09'),
            'wa_public_number' => Setting::get('wa_public_number', '6281234567890'),
            'wa_public_template' => Setting::get('wa_public_template', 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.'),
            'wa_agent_template' => Setting::get('wa_agent_template', 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'),
        ];

        $totalGlobalTarget = Branch::where('is_active', true)->sum('target_amount');

        return view('settings.index', compact('settings', 'totalGlobalTarget'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'trustbar_text' => ['nullable', 'string', 'max:160'],
            'home_quote' => ['nullable', 'string', 'max:2000'],
            'wa_reminder_enabled' => ['nullable'],
            'wa_reminder_hour' => ['required', 'numeric', 'between:0,23'],
            'wa_public_number' => ['required', 'string', 'max:30'],
            'wa_public_template' => ['nullable', 'string'],
            'wa_agent_template' => ['nullable', 'string'],
            'testimonials' => ['nullable', 'array'],
            'testimonials.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'testimonials.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'testimonials.*.photo_remove' => ['nullable', 'string', 'in:0,1'],
            'testimonials.*.text' => ['nullable', 'string', 'max:500'],
            'testimonials.*.name' => ['nullable', 'string', 'max:100'],
            'logos' => ['nullable', 'array'],
            'logos.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logos.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'logos.*.photo_remove' => ['nullable', 'string', 'in:0,1'],
        ]);

        Setting::where('key', 'global_target')->delete();

        Setting::set('trustbar_text', trim($data['trustbar_text'] ?? ''));
        Setting::set('home_quote', $this->sanitizeRichText($data['home_quote'] ?? ''));
        Setting::set('wa_reminder_enabled', $request->boolean('wa_reminder_enabled') ? '1' : '0');
        Setting::set('wa_reminder_hour', (string) (int) $data['wa_reminder_hour']);
        Setting::set('wa_public_number', $data['wa_public_number']);
        Setting::set('wa_public_template', $data['wa_public_template'] ?? '');
        Setting::set('wa_agent_template', $data['wa_agent_template'] ?? '');

        $this->saveTestimonials($request);
        $this->savePartnerLogos($request);

        ActivityLog::record('settings.update', 'Memperbarui pengaturan sistem');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    protected function sanitizeRichText(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><u><b><i><blockquote><a><ul><ol><li><span>');
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/<(a|span)[^>]*\s+(href|src)\s*=\s*(?:"|\')javascript:[^"\']*(?:"|\')/i', '<$1', $html);

        return trim($html);
    }

    protected function decodeTestimonials(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $items[] = [
                'photo' => $item['photo'] ?? '',
                'text'  => $item['text'] ?? '',
                'name'  => $item['name'] ?? '',
            ];
        }

        return $items;
    }

    protected function saveTestimonials(Request $request): void
    {
        $testimonials = [];

        foreach ((array) $request->input('testimonials', []) as $index => $row) {
            $text = trim((string) ($row['text'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($text === '' && $name === '') {
                continue;
            }

            $existing = (string) ($row['existing_photo'] ?? '');
            $removeFlag = (string) ($row['photo_remove'] ?? '0');
            $photo = $existing;

            $file = $request->file('testimonials.' . $index . '.photo');
            if ($file !== null && $file->isValid()) {
                $newPath = $file->store('testimonials', 'public');
                if ($newPath && $photo && $photo !== $newPath) {
                    $this->deleteStoredPhoto($photo);
                }
                $photo = $newPath ?: $photo;
            } elseif ($removeFlag === '1' && $photo) {
                $this->deleteStoredPhoto($photo);
                $photo = '';
            }

            $testimonials[] = [
                'photo' => $photo,
                'text'  => $text,
                'name'  => $name,
            ];
        }

        Setting::set('home_testimonials', json_encode($testimonials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function deleteStoredPhoto(string $path): void
    {
        if (preg_match('#^(https?://|/|data:)#i', $path)) {
            return;
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
    }

    protected function decodeLogos(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $logos = [];
        foreach ($decoded as $item) {
            $path = is_array($item) ? (string) ($item['photo'] ?? '') : (string) $item;
            $path = trim($path);

            if ($path !== '') {
                $logos[] = $path;
            }
        }

        return $logos;
    }

    protected function savePartnerLogos(Request $request): void
    {
        $logos = [];

        foreach ((array) $request->input('logos', []) as $index => $row) {
            $existing = (string) ($row['existing_photo'] ?? '');
            $removeFlag = (string) ($row['photo_remove'] ?? '0');
            $photo = $existing;

            $file = $request->file('logos.' . $index . '.photo');
            if ($file !== null && $file->isValid()) {
                $newPath = $file->store('partner-logos', 'public');
                if ($newPath && $photo && $photo !== $newPath) {
                    $this->deleteStoredPhoto($photo);
                }
                $photo = $newPath ?: $photo;
            } elseif ($removeFlag === '1' && $photo) {
                $this->deleteStoredPhoto($photo);
                $photo = '';
            }

            if ($photo !== '') {
                $logos[] = $photo;
            }
        }

        Setting::set('home_partner_logos', json_encode($logos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
