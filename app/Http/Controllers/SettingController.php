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
            'wa_reminder_enabled' => Setting::get('wa_reminder_enabled', '0'),
            'wa_reminder_hour' => Setting::get('wa_reminder_hour', '09'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'global_target' => ['required', 'numeric', 'min:0'],
            'wa_reminder_enabled' => ['nullable'],
            'wa_reminder_hour' => ['required', 'numeric', 'between:0,23'],
        ]);

        Setting::set('global_target', (string) $data['global_target']);
        Setting::set('wa_reminder_enabled', $request->boolean('wa_reminder_enabled') ? '1' : '0');
        Setting::set('wa_reminder_hour', (string) (int) $data['wa_reminder_hour']);

        ActivityLog::record('settings.update', 'Memperbarui pengaturan sistem');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
