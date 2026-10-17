@php
    $selectedPages = array_map('strval', old('pages', is_array($banner->pages) ? $banner->pages : array_keys(\App\Models\PopupBanner::PAGE_LABELS)));
    $selectedDays = array_map('intval', old('days', is_array($banner->days) ? $banner->days : []));
    $selectedAgents = array_map('intval', old('agent_ids', is_array($banner->agent_ids) ? $banner->agent_ids : []));
    $scheduleType = old('schedule_type', $banner->schedule_type ?: 'always');
    $agentScope = old('agent_scope', $banner->agent_scope ?: 'all');
    $triggerType = old('trigger_type', $banner->trigger_type ?: 'welcome');
@endphp

@push('styles')
<style>
    .pb-box { border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; padding: 18px; margin-bottom: 18px; }
    .pb-box h3 { margin: 0 0 12px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--gray-500, #6b7280); }
    .pb-choice { display: flex; flex-wrap: wrap; gap: 10px 22px; }
    .pb-choice label { display: inline-flex; align-items: center; gap: 7px; font-size: 14px; cursor: pointer; }
    .pb-hint { margin: 6px 0 0; font-size: 12px; color: var(--gray-500, #6b7280); }
    .pb-image-preview { max-width: 220px; max-height: 150px; object-fit: contain; border-radius: 8px; background: #f8fafc; display: block; margin-top: 8px; }
    .pb-days { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px 16px; }
</style>
@endpush

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="pb-box">
        <h3>Banner</h3>
        <div class="form-group">
            <label for="title">Judul internal *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $banner->title) }}" required>
            <p class="pb-hint">Hanya untuk identifikasi di panel admin, tidak tampil ke pengunjung.</p>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="image">Gambar popup</label>
                <input type="file" id="image" name="image" accept="image/*">
                <p class="pb-hint">Muncul sebagai modal tengah tanpa bingkai. Maks 5 MB.</p>
                @if($banner->exists && $banner->image)
                    <img id="imagePreview" class="pb-image-preview" src="{{ $banner->image_url }}" alt="{{ $banner->title }}">
                    <label class="checkbox-label" style="margin-top:8px;">
                        <input type="checkbox" name="remove_image" value="1"> Hapus gambar saat ini
                    </label>
                @else
                    <img id="imagePreview" class="pb-image-preview" src="" alt="" style="display:none;">
                @endif
            </div>
            <div class="form-group">
                <label for="url">URL tujuan (opsional)</label>
                <input type="url" id="url" name="url" value="{{ old('url', $banner->url) }}" placeholder="https://...">
                <p class="pb-hint">Bila diisi, gambar dapat diklik menuju URL ini.</p>
                <div class="form-group" style="margin-top:16px;">
                    <label for="sort_order">Urutan tampil</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="pb-box">
        <h3>Waktu tampil</h3>
        <div class="pb-choice">
            <label><input type="radio" name="schedule_type" value="always" {{ $scheduleType === 'always' ? 'checked' : '' }}> Selalu tampil</label>
            <label><input type="radio" name="schedule_type" value="range" {{ $scheduleType === 'range' ? 'checked' : '' }}> Dari tanggal sampai tanggal</label>
            <label><input type="radio" name="schedule_type" value="days" {{ $scheduleType === 'days' ? 'checked' : '' }}> Hari tertentu</label>
        </div>

        <div id="scheduleRange" style="margin-top:14px; display:none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Mulai</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', optional($banner->start_date)->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label for="end_date">Selesai</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', optional($banner->end_date)->format('Y-m-d')) }}">
                </div>
            </div>
        </div>

        <div id="scheduleDays" style="margin-top:14px; display:none;">
            <div class="pb-days">
                @foreach(\App\Models\PopupBanner::DAY_LABELS as $dayValue => $dayLabel)
                    <label class="checkbox-label">
                        <input type="checkbox" name="days[]" value="{{ $dayValue }}" {{ in_array($dayValue, $selectedDays, true) ? 'checked' : '' }}>
                        {{ $dayLabel }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="pb-box">
        <h3>Halaman tampil</h3>
        <div class="pb-choice">
            <label>
                <input type="checkbox" id="pageAll" {{ count($selectedPages) === count(\App\Models\PopupBanner::PAGE_LABELS) ? 'checked' : '' }}>
                Semua halaman
            </label>
            @foreach(\App\Models\PopupBanner::PAGE_LABELS as $pageValue => $pageLabel)
                <label>
                    <input type="checkbox" class="page-choice" name="pages[]" value="{{ $pageValue }}" {{ in_array($pageValue, $selectedPages, true) ? 'checked' : '' }}>
                    {{ $pageLabel }}
                </label>
            @endforeach
        </div>
        <p class="pb-hint">Centang halaman tempat banner boleh muncul. Halaman program milik agen termasuk Detail Program dan Halaman Agen.</p>

        <div style="margin-top:16px;">
            <div class="pb-choice">
                <label><input type="radio" name="agent_scope" value="all" {{ $agentScope === 'all' ? 'checked' : '' }}> Semua agen</label>
                <label><input type="radio" name="agent_scope" value="selected" {{ $agentScope === 'selected' ? 'checked' : '' }}> Pilih agen tertentu</label>
            </div>
            <div id="agentSelect" style="margin-top:12px; display:none; max-width:420px;">
                <label for="agent_ids">Agen</label>
                <select id="agent_ids" name="agent_ids[]" multiple size="7" style="width:100%;">
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ in_array((int) $agent->id, $selectedAgents, true) ? 'selected' : '' }}>
                            {{ $agent->name }} · {{ $agent->roleLabel() }}
                        </option>
                    @endforeach
                </select>
                <p class="pb-hint">Pilihan agen hanya berlaku di Halaman Agen.</p>
            </div>
        </div>
    </div>

    <div class="pb-box">
        <h3>Pemicu muncul</h3>
        <div class="pb-choice">
            <label><input type="radio" name="trigger_type" value="welcome" {{ $triggerType === 'welcome' ? 'checked' : '' }}> Saat halaman dibuka</label>
            <label><input type="radio" name="trigger_type" value="exit" {{ $triggerType === 'exit' ? 'checked' : '' }}> Saat pengunjung akan keluar</label>
            <label><input type="radio" name="trigger_type" value="scroll" {{ $triggerType === 'scroll' ? 'checked' : '' }}> Saat mencapai akhir halaman</label>
            <label><input type="radio" name="trigger_type" value="time" {{ $triggerType === 'time' ? 'checked' : '' }}> Setelah beberapa menit</label>
        </div>
        <div id="timeDelay" style="margin-top:12px; display:none; max-width:220px;">
            <div class="form-group">
                <label for="delay_minutes">Jeda (menit)</label>
                <input type="number" id="delay_minutes" name="delay_minutes" min="1" max="1440" value="{{ old('delay_minutes', $banner->delay_minutes ?: 1) }}">
            </div>
        </div>
        <p class="pb-hint">Banner hanya muncul sekali per sesi kunjungan setelah ditutup.</p>
    </div>

    <div class="form-group">
        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }}>
            Aktif
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <a href="{{ route('popup-banners.index') }}" class="btn">Batal</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var scheduleRadios = document.querySelectorAll('input[name="schedule_type"]');
    var rangeBox = document.getElementById('scheduleRange');
    var daysBox = document.getElementById('scheduleDays');
    var agentRadios = document.querySelectorAll('input[name="agent_scope"]');
    var agentBox = document.getElementById('agentSelect');
    var triggerRadios = document.querySelectorAll('input[name="trigger_type"]');
    var timeBox = document.getElementById('timeDelay');
    var imageInput = document.getElementById('image');
    var imagePreview = document.getElementById('imagePreview');
    var pageAll = document.getElementById('pageAll');
    var pageChoices = document.querySelectorAll('.page-choice');

    function currentValue(nodes) {
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].checked) return nodes[i].value;
        }
        return '';
    }

    function syncSchedule() {
        var value = currentValue(scheduleRadios);
        rangeBox.style.display = value === 'range' ? 'block' : 'none';
        daysBox.style.display = value === 'days' ? 'block' : 'none';
    }

    function syncAgent() {
        agentBox.style.display = currentValue(agentRadios) === 'selected' ? 'block' : 'none';
    }

    function syncTrigger() {
        timeBox.style.display = currentValue(triggerRadios) === 'time' ? 'block' : 'none';
    }

    for (var i = 0; i < scheduleRadios.length; i++) scheduleRadios[i].addEventListener('change', syncSchedule);
    for (var j = 0; j < agentRadios.length; j++) agentRadios[j].addEventListener('change', syncAgent);
    for (var k = 0; k < triggerRadios.length; k++) triggerRadios[k].addEventListener('change', syncTrigger);

    if (pageAll) {
        pageAll.addEventListener('change', function () {
            for (var p = 0; p < pageChoices.length; p++) {
                pageChoices[p].checked = pageAll.checked;
            }
        });
    }

    for (var q = 0; q < pageChoices.length; q++) {
        pageChoices[q].addEventListener('change', function () {
            var checked = 0;
            for (var r = 0; r < pageChoices.length; r++) {
                if (pageChoices[r].checked) checked++;
            }
            if (pageAll) pageAll.checked = checked === pageChoices.length;
        });
    }

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file || !imagePreview) return;
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        });
    }

    syncSchedule();
    syncAgent();
    syncTrigger();
})();
</script>
@endpush
