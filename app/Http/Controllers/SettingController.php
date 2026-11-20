<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    public function videos()
    {
        $settings = [
            'home_videos' => $this->decodeVideos(Setting::get('home_videos', '[]')),
        ];

        return view('settings.videos', compact('settings'));
    }

    public function videosUpdate(Request $request)
    {
        $this->saveVideos($request);

        ActivityLog::record('settings.update', 'Memperbarui video beranda');

        return redirect()->route('settings.videos')->with('success', 'Video beranda berhasil disimpan.');
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

    /**
     * @return array<int,array{url:string,embed:string,ratio:?float,thumbnail:?string}>
     */
    /**
     * @return array<int,array{video:string,poster:?string,ratio:float}>
     */
    protected function decodeVideos(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $videos = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $video = trim((string) ($item['video'] ?? ''));
            if ($video === '') {
                continue;
            }

            $poster = trim((string) ($item['poster'] ?? ''));
            $ratio = (float) ($item['ratio'] ?? 0);
            if ($ratio < 0.3 || $ratio > 3) {
                $ratio = 1.0;
            }

            $videos[] = [
                'video'  => $video,
                'poster' => $poster !== '' ? $poster : null,
                'ratio'  => round($ratio, 3),
            ];
        }

        return $videos;
    }

    protected function saveVideos(Request $request): void
    {
        $request->validate([
            'videos' => ['nullable', 'array'],
            'videos.*.video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:10240'],
            'videos.*.poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'videos.*.existing_video' => ['nullable', 'string', 'max:255'],
            'videos.*.existing_poster' => ['nullable', 'string', 'max:255'],
            'videos.*.ratio' => ['nullable', 'numeric'],
            'videos.*.remove' => ['nullable', 'boolean'],
        ], [
            'videos.*.video.mimetypes' => 'Video harus berformat MP4 atau WebM.',
            'videos.*.video.max' => 'Ukuran video maksimal 10 MB.',
            'videos.*.poster.image' => 'Sampul harus berupa gambar.',
            'videos.*.poster.max' => 'Ukuran sampul maksimal 2 MB.',
        ]);

        $previous = $this->decodeVideos(Setting::get('home_videos', '[]'));

        $videos = [];
        $usedVideos = [];
        $usedPosters = [];

        foreach ((array) $request->input('videos', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $index = (int) $index;

            if ((string) ($row['remove'] ?? '0') === '1') {
                continue;
            }

            $videoPath = trim((string) ($row['existing_video'] ?? ''));

            $file = $request->file('videos.' . $index . '.video');
            if ($file !== null && $file->isValid()) {
                $stored = $file->store('home-videos', 'public');
                if ($stored) {
                    $videoPath = $stored;
                }
            }

            if ($videoPath === '' || ! $this->isHomeVideoPath($videoPath, 'home-videos/')) {
                continue;
            }

            $posterPath = trim((string) ($row['existing_poster'] ?? ''));

            $posterFile = $request->file('videos.' . $index . '.poster');
            if ($posterFile !== null && $posterFile->isValid()) {
                $stored = app(\App\Services\ImageWebpService::class)->store($posterFile, 'home-video-posters');
                if ($stored) {
                    $posterPath = $stored;
                }
            }

            if (! $this->isHomeVideoPath($posterPath, 'home-video-posters/')) {
                $posterPath = '';
            }

            $ratio = (float) ($row['ratio'] ?? 0);
            if ($ratio < 0.3 || $ratio > 3) {
                $ratio = 1.0;
            }

            $videos[] = [
                'video'  => $videoPath,
                'poster' => $posterPath !== '' ? $posterPath : null,
                'ratio'  => round($ratio, 3),
            ];

            $usedVideos[] = $videoPath;
            if ($posterPath !== '') {
                $usedPosters[] = $posterPath;
            }
        }

        Setting::set('home_videos', json_encode($videos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->deleteUnusedHomeVideoFiles($previous, $usedVideos, $usedPosters);
    }

    protected function isHomeVideoPath(string $path, string $prefix): bool
    {
        $path = ltrim($path, '/');

        return $path !== ''
            && strpos($path, $prefix) === 0
            && ! preg_match('#(^|/)\.\.(/|$)#', $path);
    }

    /**
     * Hapus berkas video/sampul lama yang sudah tidak dipakai lagi.
     *
     * @param  array<int,array{video:string,poster:?string,ratio:float}>  $previous
     * @param  array<int,string>  $usedVideos
     * @param  array<int,string>  $usedPosters
     */
    protected function deleteUnusedHomeVideoFiles(array $previous, array $usedVideos, array $usedPosters): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $usedVideos = array_unique($usedVideos);
        $usedPosters = array_unique($usedPosters);

        foreach ($previous as $item) {
            $video = (string) ($item['video'] ?? '');
            if ($video !== '' && ! in_array($video, $usedVideos, true) && $this->isHomeVideoPath($video, 'home-videos/')) {
                $disk->delete(ltrim($video, '/'));
            }

            $poster = (string) ($item['poster'] ?? '');
            if ($poster !== '' && ! in_array($poster, $usedPosters, true) && $this->isHomeVideoPath($poster, 'home-video-posters/')) {
                $disk->delete(ltrim($poster, '/'));
            }
        }
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
                $newPath = app(\App\Services\ImageWebpService::class)->store($file, 'testimonials');
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
                $newPath = app(\App\Services\ImageWebpService::class)->store($file, 'partner-logos');
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
