@extends('layouts.app')

@section('title', 'Follow-up WA')

@push('styles')
<style>
    .fuwa-tabs { display:flex; gap:6px; background:#eef3f2; padding:5px; border-radius:14px; margin-bottom:18px; flex-wrap:wrap; }
    .fuwa-tab { flex:1; min-width:130px; border:none; background:transparent; padding:11px 14px; border-radius:11px; font-family:inherit; font-size:14px; font-weight:600; color:var(--gray-500); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .18s; }
    .fuwa-tab.active { background:#fff; color:var(--primary); box-shadow:0 2px 8px rgba(8,110,102,.12); }
    .fuwa-panel { display:none; }
    .fuwa-panel.active { display:block; }
    .api-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; vertical-align:middle; }
    .api-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:99px; background:#f5f8f7; font-size:12.5px; font-weight:600; }
    .fuwa-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
    .fuwa-check-row { display:flex; flex-wrap:wrap; gap:14px; margin-top:4px; }
    .fuwa-check { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--gray-700); }
    .fuwa-contact { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid #eef2f1; }
    .fuwa-contact:last-child { border-bottom:none; }
    .fuwa-preview-box { max-height:420px; overflow:auto; }
    .fuwa-hint { font-size:12px; color:var(--gray-500); margin-top:4px; }
    .fuwa-collapse-toggle { background:transparent; border:none; color:var(--primary); font-weight:600; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
</style>
@endpush

@section('content')
@php
    $dotColors = ['ok' => '#22c55e', 'fail' => '#ef4444', 'empty' => '#cbd5e1'];
    $dotLabels = ['ok' => 'Terkoneksi', 'fail' => 'Tidak terkoneksi', 'empty' => 'Belum diisi'];
    $contactStatuses = ['prospect' => 'Prospek', 'contacted' => 'Simpan', 'donated' => 'Wakif', 'churned' => 'Stop'];
    $followupBuckets = [0 => '0x', 1 => '1x', 2 => '2x', 3 => '3x+'];
@endphp

<div class="page-header">
    <div>
        <h1><i class="fab fa-whatsapp"></i> Follow-up WA</h1>
        <p class="subtitle">Broadcast otomatis, follow-up manual, dan pemanasan nomor (warming).</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <span class="api-chip"><span class="api-dot" style="background:{{ $dotColors[$connections['ss_status']] ?? '#cbd5e1' }}"></span> SS · {{ $dotLabels[$connections['ss_status']] ?? 'Belum diisi' }}</span>
        <span class="api-chip"><span class="api-dot" style="background:{{ $dotColors[$connections['cc_status']] ?? '#cbd5e1' }}"></span> CC · {{ $dotLabels[$connections['cc_status']] ?? 'Belum diisi' }}</span>
    </div>
</div>

@if($activeBroadcast)
    <div class="alert" style="background:#fff7ed;border-color:#fdba74;color:#9a3412;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
            <i class="fas fa-tower-broadcast"></i>
            <strong>Broadcast aktif:</strong> {{ $activeBroadcast->name }} ·
            terkirim {{ $activeBroadcast->sent }}/{{ $activeBroadcast->total }} ·
            gagal {{ $activeBroadcast->failed }} · balasan {{ $activeBroadcast->replies }}
        </div>
        <form method="POST" action="{{ route('followupwa.broadcast.stop', $activeBroadcast) }}" onsubmit="return confirm('Hentikan broadcast ini?');" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-stop"></i> Stop Pengiriman</button>
        </form>
    </div>
@endif

<div class="fuwa-tabs">
    <button type="button" class="fuwa-tab active" data-tab="otomatis"><i class="fas fa-robot"></i> Otomatis</button>
    <button type="button" class="fuwa-tab" data-tab="manual"><i class="fas fa-hand-pointer"></i> Manual</button>
    <button type="button" class="fuwa-tab" data-tab="warming"><i class="fas fa-fire"></i> Warming</button>
</div>

{{-- ===================== TAB OTOMATIS ===================== --}}
<div class="fuwa-panel active" id="fuwa-panel-otomatis">
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <h2 style="margin:0;font-size:16px;"><i class="fas fa-sliders" style="color:var(--primary);"></i> Konfigurasi Broadcast</h2>
            <button type="button" class="fuwa-collapse-toggle" data-collapse="otomatis-config"><i class="fas fa-chevron-down"></i> Tampilkan / sembunyikan</button>
        </div>

        <div id="otomatis-config" hidden>
            <form method="POST" action="{{ route('followupwa.broadcast.store') }}" id="fuwa-broadcast-form">
                @csrf
                <div class="fuwa-grid">
                    <div class="form-group">
                        <label>Nama Broadcast</label>
                        <input type="text" name="name" placeholder="Promo Wakif Oktober">
                    </div>
                    <div class="form-group">
                        <label>Mekanisme</label>
                        <select name="mechanism" id="fuwa-mechanism">
                            <option value="auto">Otomatis (10 kontak, lanjut 5 tiap balasan)</option>
                            <option value="limit">Terbatas (sesuai jumlah)</option>
                        </select>
                    </div>
                    <div class="form-group" id="fuwa-limit-group" hidden>
                        <label>Jumlah Kontak</label>
                        <input type="number" name="limit_count" min="1" value="20">
                    </div>
                    <div class="form-group">
                        <label>Jadwal</label>
                        <select name="schedule_type" id="fuwa-schedule-type">
                            <option value="now">Kirim Sekarang</option>
                            <option value="scheduled">Terjadwal</option>
                        </select>
                    </div>
                    <div class="form-group" id="fuwa-scheduled-group" hidden>
                        <label>Waktu Mulai</label>
                        <input type="datetime-local" name="scheduled_at">
                    </div>
                    <div class="form-group">
                        <label>Batas Waktu Stop (opsional)</label>
                        <input type="datetime-local" name="stop_at">
                    </div>
                    <div class="form-group">
                        <label>Jeda Min (detik)</label>
                        <input type="number" name="interval_min" min="5" value="20">
                    </div>
                    <div class="form-group">
                        <label>Jeda Maks (detik)</label>
                        <input type="number" name="interval_max" min="5" value="60">
                    </div>
                </div>

                <div class="fuwa-grid">
                    <div class="form-group">
                        <label>Cabang (opsional)</label>
                        <select name="branch_id">
                            <option value="">Semua Cabang</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Agen (opsional)</label>
                        <select name="agen_id">
                            <option value="">Semua Agen</option>
                            @foreach($agens as $agen)
                                <option value="{{ $agen->id }}">{{ $agen->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status Kontak</label>
                    <div class="fuwa-check-row">
                        @foreach($contactStatuses as $value => $label)
                            <label class="fuwa-check"><input type="checkbox" name="statuses[]" value="{{ $value }}" @if($value !== 'churned') checked @endif> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label>Riwayat Follow-up</label>
                    <div class="fuwa-check-row">
                        @foreach($followupBuckets as $value => $label)
                            <label class="fuwa-check"><input type="checkbox" name="followups[]" value="{{ $value }}" checked> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label>Media URL (opsional)</label>
                    <input type="text" name="media_path" placeholder="https://.../gambar.jpg">
                    <div class="fuwa-hint">Untuk media. Jenis media dipilih di samping. Biarkan kosong untuk pesan teks.</div>
                </div>
                <div class="form-group">
                    <label>Jenis Media</label>
                    <select name="media_type">
                        <option value="">Tidak ada</option>
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="document">Dokumen</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Template Pesan *</label>
                    <textarea name="message" id="fuwa-broadcast-message" rows="5" required placeholder="Assalamualaikum [nama], {Halo|Hai} ...">{{ old('message') }}</textarea>
                    <div class="fuwa-hint">Placeholder: <code>[nama]</code>, <code>[nomor]</code>, <code>[nama_agen]</code>, <code>[cabang]</code>. Variasi acak: <code>{Halo|Hai|Assalamualaikum}</code>.</div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" id="fuwa-preview-otomatis"><i class="fas fa-eye"></i> Ambil Kontak</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Mulai Broadcast</button>
                </div>
            </form>
        </div>

        <div id="otomatis-preview" style="margin-top:14px;"></div>
    </div>

    <div class="card">
        <h2 style="margin:0 0 12px;font-size:16px;"><i class="fas fa-list-check" style="color:var(--primary);"></i> Riwayat Broadcast</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Nama</th><th>Pengguna</th><th>Mekanisme</th><th>Status</th><th>Progress</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                    @forelse($broadcasts as $b)
                        <tr>
                            <td>{{ $b->name }}</td>
                            <td>{{ $b->user->name ?? '-' }}</td>
                            <td>{{ $b->mechanism === 'auto' ? 'Otomatis' : 'Terbatas' }}</td>
                            <td>
                                @php $cls = ['completed'=>'badge-green','running'=>'badge-orange','stopped'=>'badge-gray','failed'=>'badge-red'][$b->status] ?? 'badge-gray'; @endphp
                                <span class="badge {{ $cls }}">{{ $b->statusLabel() }}</span>
                            </td>
                            <td>{{ $b->sent }}/{{ $b->total }} terkirim · {{ $b->failed }} gagal · {{ $b->replies }} balasan</td>
                            <td>{{ $b->created_at ? $b->created_at->format('d M Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-inbox"></i><p>Belum ada broadcast.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===================== TAB MANUAL ===================== --}}
<div class="fuwa-panel" id="fuwa-panel-manual">
    <div class="card">
        <h2 style="margin:0 0 12px;font-size:16px;"><i class="fas fa-hand-pointer" style="color:var(--primary);"></i> Follow-up Manual</h2>
        <p class="subtitle" style="margin-top:0;">Ambil kontak, buka WhatsApp, lalu tandai "Terkirim" untuk mencatat follow-up.</p>

        <div class="fuwa-grid">
            <div class="form-group">
                <label>Cabang (opsional)</label>
                <select id="fuwa-manual-branch">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Agen (opsional)</label>
                <select id="fuwa-manual-agen">
                    <option value="">Semua Agen</option>
                    @foreach($agens as $agen)
                        <option value="{{ $agen->id }}">{{ $agen->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Status Kontak</label>
            <div class="fuwa-check-row">
                @foreach($contactStatuses as $value => $label)
                    <label class="fuwa-check"><input type="checkbox" class="fuwa-manual-status" value="{{ $value }}" @if(in_array($value, ['prospect','contacted'])) checked @endif> {{ $label }}</label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label>Riwayat Follow-up</label>
            <div class="fuwa-check-row">
                @foreach($followupBuckets as $value => $label)
                    <label class="fuwa-check"><input type="checkbox" class="fuwa-manual-followup" value="{{ $value }}" checked> {{ $label }}</label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label>Template Pesan</label>
            <textarea id="fuwa-manual-message" rows="4" placeholder="Assalamualaikum [nama], ..."></textarea>
            <div class="fuwa-hint">Placeholder: <code>[nama]</code>, <code>[nomor]</code>, <code>[nama_agen]</code>.</div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-primary" id="fuwa-preview-manual"><i class="fas fa-eye"></i> Ambil Kontak</button>
        </div>

        <div id="manual-preview" style="margin-top:14px;"></div>
    </div>
</div>

{{-- ===================== TAB WARMING ===================== --}}
<div class="fuwa-panel" id="fuwa-panel-warming">
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <h2 style="margin:0;font-size:16px;"><i class="fas fa-fire" style="color:var(--primary);"></i> Warming Nomor</h2>
            <button type="button" class="fuwa-collapse-toggle" data-collapse="warming-config"><i class="fas fa-chevron-down"></i> Tampilkan / sembunyikan</button>
        </div>

        <div id="warming-config" hidden>
            <form method="POST" action="{{ route('followupwa.warming') }}">
                @csrf
                <div class="fuwa-grid">
                    <div class="form-group">
                        <label>Jumlah Pasangan</label>
                        <input type="number" name="amount_pair" min="1" value="{{ $warmingConfig['amount_pair'] }}">
                    </div>
                    <div class="form-group">
                        <label>Jeda Min (detik)</label>
                        <input type="number" name="interval_min" min="5" value="{{ $warmingConfig['interval_min'] }}">
                    </div>
                    <div class="form-group">
                        <label>Jeda Maks (detik)</label>
                        <input type="number" name="interval_max" min="5" value="{{ $warmingConfig['interval_max'] }}">
                    </div>
                    <div class="form-group">
                        <label>Jam Mulai</label>
                        <input type="time" name="start_time" value="{{ $warmingConfig['start_time'] }}">
                    </div>
                    <div class="form-group">
                        <label>Jam Selesai</label>
                        <input type="time" name="stop_time" value="{{ $warmingConfig['stop_time'] }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Template Pesan Warming</label>
                    <textarea name="messages" rows="3">{{ $warmingConfig['messages'] }}</textarea>
                </div>
                <label class="fuwa-check"><input type="checkbox" name="active" value="1" @if($warmingConfig['active']) checked @endif> Aktifkan warming</label>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Konfigurasi</button>
                </div>
            </form>

            <form method="POST" action="{{ route('followupwa.warming.run') }}" style="margin-top:10px;">
                @csrf
                <div class="form-actions" style="justify-content:flex-start;">
                    <input type="number" name="amount" min="1" max="50" value="5" style="max-width:110px;">
                    <button type="submit" class="btn"><i class="fas fa-fire"></i> Jalankan Warming Sekarang</button>
                </div>
            </form>
        </div>

        <div class="table-responsive" style="margin-top:14px;">
            <table class="table">
                <thead>
                    <tr><th>Pengguna</th><th>Nomor</th><th>API SS</th><th>API CC</th><th>Kirim Hari Ini</th><th>Terima Hari Ini</th></tr>
                </thead>
                <tbody>
                    @forelse($warmingRecipients as $r)
                        <tr>
                            <td>{{ $r['name'] }} <small style="color:var(--gray-500);">· {{ $r['role'] }}</small></td>
                            <td>{{ $r['phone'] }}</td>
                            <td><span class="api-dot" style="background:{{ $dotColors[$r['ss']] ?? '#cbd5e1' }}"></span> {{ $dotLabels[$r['ss']] ?? '-' }}</td>
                            <td><span class="api-dot" style="background:{{ $dotColors[$r['cc']] ?? '#cbd5e1' }}"></span> {{ $dotLabels[$r['cc']] ?? '-' }}</td>
                            <td>{{ $r['sent'] }}</td>
                            <td>{{ $r['received'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-users"></i><p>Belum ada pengguna tujuan warming.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="alert" style="background:#E9F0FA;border-color:var(--primary);color:var(--primary);margin-top:14px;word-break:break-all;">
            <i class="fas fa-link"></i> URL Webhook: <code>{{ $webhookUrl }}</code>
        </div>
    </div>

    <div class="card">
        <h2 style="margin:0 0 12px;font-size:16px;"><i class="fas fa-clock-rotate-left" style="color:var(--primary);"></i> Log Terakhir</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Kontak</th><th>No. Tujuan</th><th>Pesan</th><th>Status</th><th>Waktu</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->contact->name ?? '-' }}</td>
                            <td>{{ $log->phone }}</td>
                            <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $log->message }}</td>
                            <td>
                                @php $cls = ['sent'=>'badge-green','pending'=>'badge-orange','failed'=>'badge-red'][$log->status] ?? 'badge-gray'; @endphp
                                <span class="badge {{ $cls }}">{{ ucfirst($log->status) }}</span>
                            </td>
                            <td>{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('followupwa.log.destroy', $log) }}" onsubmit="return confirm('Hapus log ini?');" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-inbox"></i><p>Belum ada log pengiriman.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var cfg = {
        contactsOtomatis: '{{ route('followupwa.contacts') }}',
        contactsManual: '{{ route('followupwa.contacts') }}',
        manualLog: '{{ route('followupwa.manual') }}',
        csrf: document.querySelector('meta[name="csrf-token"]').content
    };

    // Tabs
    document.querySelectorAll('.fuwa-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.fuwa-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.fuwa-panel').forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            var panel = document.getElementById('fuwa-panel-' + tab.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // Collapse toggles
    document.querySelectorAll('.fuwa-collapse-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.collapse);
            if (target) target.hidden = !target.hidden;
        });
    });

    // Mechanism / schedule toggles
    var mechanism = document.getElementById('fuwa-mechanism');
    var limitGroup = document.getElementById('fuwa-limit-group');
    if (mechanism && limitGroup) {
        mechanism.addEventListener('change', function () {
            limitGroup.hidden = mechanism.value !== 'limit';
        });
    }
    var scheduleType = document.getElementById('fuwa-schedule-type');
    var scheduledGroup = document.getElementById('fuwa-scheduled-group');
    if (scheduleType && scheduledGroup) {
        scheduleType.addEventListener('change', function () {
            scheduledGroup.hidden = scheduleType.value !== 'scheduled';
        });
    }

    function collectList(selector) {
        return Array.prototype.slice.call(document.querySelectorAll(selector + ':checked')).map(function (el) { return el.value; });
    }

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function renderPreview(container, data, manual) {
        if (!data.contacts || data.contacts.length === 0) {
            container.innerHTML = '<div class="alert" style="background:#f8fafb;border-color:#e2e8f0;color:var(--gray-500);"><i class="fas fa-circle-info"></i> Tidak ada kontak yang cocok.</div>';
            return;
        }
        var html = '<div class="alert" style="background:#eefaf8;border-color:var(--primary);color:var(--primary);"><i class="fas fa-users"></i> <strong>' + data.count + '</strong> kontak ditemukan.</div>';
        html += '<div class="fuwa-preview-box">';
        data.contacts.forEach(function (c) {
            html += '<div class="fuwa-contact">';
            html += '<div style="min-width:0;">';
            html += '<div style="font-weight:600;">' + esc(c.name) + ' <span class="badge badge-gray">' + esc(c.status_label) + '</span> <span class="badge badge-blue">' + esc(c.followup_count) + 'x</span></div>';
            html += '<div style="font-size:12.5px;color:var(--gray-500);">' + esc(c.phone) + '</div>';
            if (c.message) html += '<div style="font-size:12.5px;color:var(--gray-700);margin-top:4px;white-space:pre-wrap;">' + esc(c.message) + '</div>';
            html += '</div>';
            if (manual) {
                html += '<div style="display:flex;gap:6px;flex-shrink:0;">';
                html += '<a href="' + esc(c.wa_link) + '" target="_blank" rel="noopener" class="btn btn-sm btn-primary"><i class="fab fa-whatsapp"></i> Buka</a>';
                html += '<button type="button" class="btn btn-sm fuwa-mark" data-id="' + esc(c.id) + '" data-msg="' + encodeURIComponent(c.message || '') + '"><i class="fas fa-check"></i> Terkirim</button>';
                html += '</div>';
            }
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function fetchContacts(url, payload, container, manual) {
        container.innerHTML = '<div class="alert" style="background:#f5f8f7;border-color:#e2e8f0;color:var(--gray-500);"><i class="fas fa-spinner fa-spin"></i> Memuat kontak...</div>';
        var body = new URLSearchParams();
        Object.keys(payload).forEach(function (key) {
            var val = payload[key];
            if (Array.isArray(val)) {
                val.forEach(function (v) { body.append(key + '[]', v); });
            } else if (val !== null && val !== undefined && val !== '') {
                body.append(key, val);
            }
        });

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (r) { return r.json(); }).then(function (data) {
            renderPreview(container, data, manual);
            if (manual) bindMarkButtons(container);
        }).catch(function () {
            container.innerHTML = '<div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> Gagal memuat kontak.</div>';
        });
    }

    function bindMarkButtons(container) {
        container.querySelectorAll('.fuwa-mark').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.disabled = true;
                var body = new URLSearchParams();
                body.append('contact_id', btn.dataset.id);
                body.append('message', decodeURIComponent(btn.dataset.msg || ''));
                fetch(cfg.manualLog, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                }).then(function (r) { return r.json(); }).then(function () {
                    btn.innerHTML = '<i class="fas fa-check-double"></i> Tercatat';
                    btn.classList.remove('btn-primary');
                }).catch(function () {
                    btn.disabled = false;
                });
            });
        });
    }

    var previewOtomatis = document.getElementById('fuwa-preview-otomatis');
    if (previewOtomatis) {
        previewOtomatis.addEventListener('click', function () {
            fetchContacts(cfg.contactsOtomatis, {
                branch_id: document.querySelector('#fuwa-broadcast-form [name=branch_id]').value,
                agen_id: document.querySelector('#fuwa-broadcast-form [name=agen_id]').value,
                statuses: collectList('#fuwa-broadcast-form [name="statuses[]"]'),
                followups: collectList('#fuwa-broadcast-form [name="followups[]"]'),
                message: document.getElementById('fuwa-broadcast-message').value
            }, document.getElementById('otomatis-preview'), false);
        });
    }

    var previewManual = document.getElementById('fuwa-preview-manual');
    if (previewManual) {
        previewManual.addEventListener('click', function () {
            fetchContacts(cfg.contactsManual, {
                branch_id: document.getElementById('fuwa-manual-branch').value,
                agen_id: document.getElementById('fuwa-manual-agen').value,
                statuses: collectList('.fuwa-manual-status'),
                followups: collectList('.fuwa-manual-followup'),
                message: document.getElementById('fuwa-manual-message').value
            }, document.getElementById('manual-preview'), true);
        });
    }
})();
</script>
@endpush
