<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PopupBanner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PopupBannerController extends Controller
{
    public function index()
    {
        $banners = PopupBanner::orderBy('sort_order')->orderBy('id')->paginate(10);

        $statsSettings = [
            'enabled'       => (string) Setting::get('popup_stats_enabled', '1') !== '0',
            'delay_seconds' => max(0, (int) Setting::get('popup_stats_delay_seconds', 8)),
        ];

        return view('popup-banners.index', compact('banners', 'statsSettings'));
    }

    public function create()
    {
        return view('popup-banners.create', [
            'banner' => new PopupBanner([
                'schedule_type' => 'always',
                'pages'         => array_keys(PopupBanner::PAGE_LABELS),
                'agent_scope'   => 'all',
                'trigger_type'  => 'welcome',
                'delay_minutes' => 1,
                'is_active'     => true,
            ]),
            'agents' => $this->agents(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('popup-banners', 'public');
        }

        $data = $this->normalize($request, $data);
        $data['is_active'] = $request->boolean('is_active');

        PopupBanner::create($data);

        ActivityLog::record('popup-banner.create', 'Membuat banner popup ' . $data['title']);

        return redirect()->route('popup-banners.index')->with('success', 'Banner berhasil dibuat.');
    }

    public function edit(PopupBanner $popupBanner)
    {
        return view('popup-banners.edit', [
            'banner' => $popupBanner,
            'agents' => $this->agents(),
        ]);
    }

    public function update(Request $request, PopupBanner $popupBanner)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($popupBanner->image) {
                Storage::disk('public')->delete($popupBanner->image);
            }
            $data['image'] = $request->file('image')->store('popup-banners', 'public');
        } elseif ($request->boolean('remove_image') && $popupBanner->image) {
            Storage::disk('public')->delete($popupBanner->image);
            $data['image'] = null;
        }

        unset($data['remove_image']);

        $data = $this->normalize($request, $data);
        $data['is_active'] = $request->boolean('is_active');

        $popupBanner->update($data);

        ActivityLog::record('popup-banner.update', 'Memperbarui banner popup ' . $popupBanner->title);

        return redirect()->route('popup-banners.index')->with('success', 'Banner berhasil diperbarui.');
    }

    public function destroy(PopupBanner $popupBanner)
    {
        if ($popupBanner->image) {
            Storage::disk('public')->delete($popupBanner->image);
        }

        ActivityLog::record('popup-banner.delete', 'Menghapus banner popup ' . $popupBanner->title);
        $popupBanner->delete();

        return redirect()->route('popup-banners.index')->with('success', 'Banner berhasil dihapus.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'popup_stats_enabled'      => ['boolean'],
            'popup_stats_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
        ]);

        Setting::set('popup_stats_enabled', $request->boolean('popup_stats_enabled') ? '1' : '0');
        Setting::set('popup_stats_delay_seconds', (string) max(0, (int) ($data['popup_stats_delay_seconds'] ?? 8)));

        ActivityLog::record('popup-banner.settings', 'Memperbarui setelan popup statistik');

        return redirect()->route('popup-banners.index')->with('success', 'Setelan popup statistik berhasil disimpan.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'image'          => ['nullable', 'image', 'max:5120'],
            'remove_image'   => ['boolean'],
            'url'            => ['nullable', 'url', 'max:500'],
            'schedule_type'  => ['required', Rule::in(['always', 'range', 'days'])],
            'start_date'     => ['nullable', 'required_if:schedule_type,range', 'date'],
            'end_date'       => ['nullable', 'required_if:schedule_type,range', 'date', 'after_or_equal:start_date'],
            'days'           => ['nullable', 'array', 'required_if:schedule_type,days'],
            'days.*'         => ['integer', 'between:0,6'],
            'pages'          => ['required', 'array', 'min:1'],
            'pages.*'        => [Rule::in(array_keys(PopupBanner::PAGE_LABELS))],
            'agent_scope'    => ['required', Rule::in(['all', 'selected'])],
            'agent_ids'      => ['nullable', 'array', 'required_if:agent_scope,selected'],
            'agent_ids.*'    => ['exists:users,id'],
            'trigger_type'   => ['required', Rule::in(array_keys(PopupBanner::TRIGGER_LABELS))],
            'delay_minutes'  => ['nullable', 'integer', 'min:1', 'max:1440'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['boolean'],
        ]);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function normalize(Request $request, array $data): array
    {
        unset($data['remove_image']);

        if ($data['schedule_type'] !== 'range') {
            $data['start_date'] = null;
            $data['end_date'] = null;
        }

        if ($data['schedule_type'] !== 'days') {
            $data['days'] = null;
        } else {
            $data['days'] = array_values(array_unique(array_map('intval', $data['days'] ?? [])));
        }

        $data['pages'] = array_values(array_unique($data['pages'] ?? []));
        $data['agent_scope'] = $data['agent_scope'] ?? 'all';
        $data['agent_ids'] = $data['agent_scope'] === 'selected'
            ? array_values(array_unique(array_map('intval', $data['agent_ids'] ?? [])))
            : null;

        $data['delay_minutes'] = $data['trigger_type'] === 'time'
            ? max(1, (int) ($data['delay_minutes'] ?? 1))
            : 1;

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    protected function agents()
    {
        return User::whereIn('role', [User::ROLE_AGEN, User::ROLE_SUPERVISOR])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
