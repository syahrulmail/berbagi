<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;

class MobileSettingController extends SettingController
{
    public function index()
    {
        $settings = $this->generalSettings();

        return view('mobile.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->saveGeneralSettings($request);

        ActivityLog::record('settings.update', 'Memperbarui pengaturan sistem');

        return redirect()->route('mo.settings')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function targetInfo()
    {
        $settings = $this->targetInfoSettings();
        $totalGlobalTarget = Branch::where('is_active', true)->sum('target_amount');

        return view('mobile.settings.target', compact('settings', 'totalGlobalTarget'));
    }

    public function targetInfoUpdate(Request $request)
    {
        $this->saveTargetInfo($request);

        ActivityLog::record('settings.update', 'Memperbarui target & informasi');

        return redirect()->route('mo.settings.target')->with('success', 'Target & informasi berhasil disimpan.');
    }

    public function testimonials()
    {
        $settings = [
            'home_testimonials' => $this->decodeTestimonials(Setting::get('home_testimonials', '[]')),
        ];

        return view('mobile.settings.testimonials', compact('settings'));
    }

    public function testimonialsUpdate(Request $request)
    {
        $this->saveTestimonialsData($request);

        ActivityLog::record('settings.update', 'Memperbarui testimoni tokoh');

        return redirect()->route('mo.settings.testimonials')->with('success', 'Testimoni berhasil disimpan.');
    }

    public function partners()
    {
        $settings = [
            'home_partner_logos' => $this->decodeLogos(Setting::get('home_partner_logos', '[]')),
        ];

        return view('mobile.settings.partners', compact('settings'));
    }

    public function partnersUpdate(Request $request)
    {
        $this->savePartnerLogosData($request);

        ActivityLog::record('settings.update', 'Memperbarui logo mitra');

        return redirect()->route('mo.settings.partners')->with('success', 'Logo mitra berhasil disimpan.');
    }
}
