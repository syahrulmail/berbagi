<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $settings = $this->generalSettings();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->saveGeneralSettings($request);

        ActivityLog::record('settings.update', 'Memperbarui pengaturan sistem');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function targetInfo()
    {
        $settings = $this->targetInfoSettings();
        $totalGlobalTarget = Branch::where('is_active', true)->sum('target_amount');

        return view('settings.target', compact('settings', 'totalGlobalTarget'));
    }

    public function targetInfoUpdate(Request $request)
    {
        $this->saveTargetInfo($request);

        ActivityLog::record('settings.update', 'Memperbarui target & informasi');

        return redirect()->route('settings.target')->with('success', 'Target & informasi berhasil disimpan.');
    }

    public function testimonials()
    {
        $settings = [
            'home_testimonials' => $this->decodeTestimonials(Setting::get('home_testimonials', '[]')),
        ];

        return view('settings.testimonials', compact('settings'));
    }

    public function testimonialsUpdate(Request $request)
    {
        $this->saveTestimonialsData($request);

        ActivityLog::record('settings.update', 'Memperbarui testimoni tokoh');

        return redirect()->route('settings.testimonials')->with('success', 'Testimoni berhasil disimpan.');
    }

    public function partners()
    {
        $settings = [
            'home_partner_logos' => $this->decodeLogos(Setting::get('home_partner_logos', '[]')),
        ];

        return view('settings.partners', compact('settings'));
    }

    public function partnersUpdate(Request $request)
    {
        $this->savePartnerLogosData($request);

        ActivityLog::record('settings.update', 'Memperbarui logo mitra');

        return redirect()->route('settings.partners')->with('success', 'Logo mitra berhasil disimpan.');
    }

    protected function generalSettings(): array
    {
        return [
            'wa_public_number' => Setting::get('wa_public_number', '6281234567890'),
            'wa_public_template' => Setting::get('wa_public_template', 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.'),
            'wa_agent_template' => Setting::get('wa_agent_template', 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'),
            'google_site_verification' => Setting::get('google_site_verification', ''),
            'gtm_container_id' => Setting::get('gtm_container_id', ''),
            'ga_measurement_id' => Setting::get('ga_measurement_id', ''),
            'head_tracking_code' => Setting::get('head_tracking_code', ''),
            'body_tracking_code' => Setting::get('body_tracking_code', ''),
            'lapdfbwa_password_set' => (bool) Setting::get('lapdfbwa_password'),
        ];
    }

    protected function targetInfoSettings(): array
    {
        return [
            'trustbar_text' => Setting::get('trustbar_text', 'Badan Wakaf Al Qur\'an · Terdaftar & Berizin'),
            'home_quote' => Setting::get('home_quote', '<p>&quot;Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.&quot; — <strong>HR. Ahmad &amp; Thabrani</strong></p>'),
        ];
    }

    protected function saveGeneralSettings(Request $request): void
    {
        $data = $request->validate([
            'wa_public_number' => ['required', 'string', 'max:30'],
            'wa_public_template' => ['nullable', 'string'],
            'wa_agent_template' => ['nullable', 'string'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'gtm_container_id' => ['nullable', 'string', 'max:50'],
            'ga_measurement_id' => ['nullable', 'string', 'max:50'],
            'head_tracking_code' => ['nullable', 'string', 'max:20000'],
            'body_tracking_code' => ['nullable', 'string', 'max:20000'],
            'lapdfbwa_password' => ['nullable', 'string', 'min:4', 'max:60'],
        ]);

        Setting::set('wa_public_number', $data['wa_public_number']);
        Setting::set('wa_public_template', $data['wa_public_template'] ?? '');
        Setting::set('wa_agent_template', $data['wa_agent_template'] ?? '');

        Setting::set('google_site_verification', trim($data['google_site_verification'] ?? ''));
        Setting::set('gtm_container_id', trim($data['gtm_container_id'] ?? ''));
        Setting::set('ga_measurement_id', trim($data['ga_measurement_id'] ?? ''));
        Setting::set('head_tracking_code', trim($data['head_tracking_code'] ?? ''));
        Setting::set('body_tracking_code', trim($data['body_tracking_code'] ?? ''));

        if ($request->filled('lapdfbwa_password')) {
            Setting::set('lapdfbwa_password', Hash::make($request->input('lapdfbwa_password')));
        } elseif ($request->boolean('lapdfbwa_password_clear')) {
            Setting::set('lapdfbwa_password', '');
        }
    }

    protected function saveTargetInfo(Request $request): void
    {
        $data = $request->validate([
            'trustbar_text' => ['nullable', 'string', 'max:160'],
            'home_quote' => ['nullable', 'string', 'max:2000'],
        ]);

        Setting::set('trustbar_text', trim($data['trustbar_text'] ?? ''));
        Setting::set('home_quote', $this->sanitizeRichText($data['home_quote'] ?? ''));
    }

    protected function saveTestimonialsData(Request $request): void
    {
        $request->validate([
            'testimonials' => ['nullable', 'array'],
            'testimonials.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'testimonials.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'testimonials.*.photo_remove' => ['nullable', 'string', 'in:0,1'],
            'testimonials.*.text' => ['nullable', 'string', 'max:500'],
            'testimonials.*.name' => ['nullable', 'string', 'max:100'],
        ]);

        $this->saveTestimonials($request);
    }

    protected function savePartnerLogosData(Request $request): void
    {
        $request->validate([
            'logos' => ['nullable', 'array'],
            'logos.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logos.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'logos.*.photo_remove' => ['nullable', 'string', 'in:0,1'],
        ]);

        $this->savePartnerLogos($request);
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
