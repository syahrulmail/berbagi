<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'global_target' => Setting::get('global_target', '1500000000'),
            'trustbar_text' => Setting::get('trustbar_text', 'Badan Wakaf Al Qur\'an · Terdaftar & Berizin'),
            'home_quote' => Setting::get('home_quote', '<p>&quot;Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.&quot; — <strong>HR. Ahmad &amp; Thabrani</strong></p>'),
            'wa_reminder_enabled' => Setting::get('wa_reminder_enabled', '0'),
            'wa_reminder_hour' => Setting::get('wa_reminder_hour', '09'),
            'wa_public_number' => Setting::get('wa_public_number', '6281234567890'),
            'wa_public_template' => Setting::get('wa_public_template', 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.'),
            'wa_agent_template' => Setting::get('wa_agent_template', 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'global_target' => ['required', 'numeric', 'min:0'],
            'trustbar_text' => ['nullable', 'string', 'max:160'],
            'home_quote' => ['nullable', 'string', 'max:2000'],
            'wa_reminder_enabled' => ['nullable'],
            'wa_reminder_hour' => ['required', 'numeric', 'between:0,23'],
            'wa_public_number' => ['required', 'string', 'max:30'],
            'wa_public_template' => ['nullable', 'string'],
            'wa_agent_template' => ['nullable', 'string'],
        ]);

        Setting::set('global_target', (string) $data['global_target']);
        Setting::set('trustbar_text', trim($data['trustbar_text'] ?? ''));
        Setting::set('home_quote', $this->sanitizeRichText($data['home_quote'] ?? ''));
        Setting::set('wa_reminder_enabled', $request->boolean('wa_reminder_enabled') ? '1' : '0');
        Setting::set('wa_reminder_hour', (string) (int) $data['wa_reminder_hour']);
        Setting::set('wa_public_number', $data['wa_public_number']);
        Setting::set('wa_public_template', $data['wa_public_template'] ?? '');
        Setting::set('wa_agent_template', $data['wa_agent_template'] ?? '');

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
}
