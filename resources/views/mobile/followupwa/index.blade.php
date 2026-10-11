@extends('mobile.layouts.app')

@section('title', 'Follow-up WA')

@section('mobile-content')
@php
    $dotColors = ['ok' => '#22c55e', 'fail' => '#ef4444', 'empty' => '#cbd5e1'];
    $dotLabels = ['ok' => 'Terkoneksi', 'fail' => 'Tidak terkoneksi', 'empty' => 'Belum diisi'];
    $contactStatuses = ['prospect' => 'Prospek', 'contacted' => 'Simpan', 'donated' => 'Wakif', 'churned' => 'Stop'];
    $followupBuckets = [0 => '0x', 1 => '1x', 2 => '2x', 3 => '3x+'];
@endphp

<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fab fa-whatsapp" style="color:#25d366;font-size:19px;"></i> Follow-up WA</h1>
            <div class="mo-appbar-sub">Broadcast · Manual · Warming · Log</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <span class="mo-badge gray" style="display:inline-flex;align-items:center;gap:6px;">
            <span style="width:9px;height:9px;border-radius:50%;background:{{ $dotColors[$connections['ss_status']] ?? '#cbd5e1' }}"></span> SS {{ $dotLabels[$connections['ss_status']] ?? '-' }}
        </span>
        <span class="mo-badge gray" style="display:inline-flex;align-items:center;gap:6px;">
            <span style="width:9px;height:9px;border-radius:50%;background:{{ $dotColors[$connections['cc_status']] ?? '#cbd5e1' }}"></span> CC {{ $dotLabels[$connections['cc_status']] ?? '-' }}
        </span>
    </div>

    @if($activeBroadcast)
        <div class="mo-form-card" style="border:1px solid #fdba74;">
            <div style="font-size:13px;font-weight:700;color:#9a3412;"><i class="fas fa-tower-broadcast"></i> {{ $activeBroadcast->name }}</div>
            <div style="font-size:12px;color:var(--mo-muted);margin-top:4px;">Terkirim {{ $activeBroadcast->sent }}/{{ $activeBroadcast->total }} · gagal {{ $activeBroadcast->failed }} · balasan {{ $activeBroadcast->replies }}</div>
            <form method="POST" action="{{ route('mo.whatsapp.broadcast.stop', $activeBroadcast) }}" onsubmit="return confirm('Hentikan broadcast ini?');" style="margin-top:10px;">
                @csrf
                <button type="submit" class="mo-btn mo-btn-primary" style="background:var(--mo-danger);width:100%;"><i class="fas fa-stop"></i> Stop Pengiriman</button>
            </form>
        </div>
    @endif

    <div class="mo-segmented" style="margin-bottom:14px;">
        <button type="button" class="mo-segmented-item active" data-tab="otomatis">Otomatis</button>
        <button type="button" class="mo-segmented-item" data-tab="manual">Manual</button>
        <button type="button" class="mo-segmented-item" data-tab="warming">Warming</button>
        <button type="button" class="mo-segmented-item" data-tab="log">Log</button>
    </div>

    {{-- OTOMATIS --}}
    <div id="mo-fuwa-otomatis">
        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-robot"></i> Broadcast Otomatis</div>
            <form method="POST" action="{{ route('mo.whatsapp.broadcast') }}" enctype="multipart/form-data">
                @csrf
                <div class="mo-field">
                    <label>Nama Broadcast</label>
                    <input type="text" name="name" class="mo-input" placeholder="Promo Wakif">
                </div>
                <div class="mo-field">
                    <label>Mekanisme</label>
                    <select name="mechanism" class="mo-select" id="mo-mechanism">
                        <option value="auto">Otomatis (lanjut 5 tiap balasan)</option>
                        <option value="limit">Terbatas (jumlah)</option>
                    </select>
                </div>
                <div class="mo-field" id="mo-limit-group">
                    <label>Jumlah Kontak</label>
                    <input type="number" name="limit_count" class="mo-input" min="1" value="20">
                </div>
                <div class="mo-field">
                    <label>Jadwal</label>
                    <select name="schedule_type" class="mo-select" id="mo-schedule-type">
                        <option value="now">Kirim Sekarang</option>
                        <option value="scheduled">Terjadwal</option>
                    </select>
                </div>
                <div class="mo-field" id="mo-scheduled-group" hidden>
                    <label>Waktu Mulai</label>
                    <input type="datetime-local" name="scheduled_at" class="mo-input" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="mo-field">
                    <label>Batas Waktu Stop</label>
                    <input type="datetime-local" name="stop_at" class="mo-input" value="{{ now()->format('Y-m-d') }}T17:00">
                </div>
                <div style="display:flex;gap:10px;">
                    <div class="mo-field" style="flex:1;">
                        <label>Jeda Min (dtk)</label>
                        <input type="number" name="interval_min" class="mo-input" min="5" value="100">
                    </div>
                    <div class="mo-field" style="flex:1;">
                        <label>Jeda Maks (dtk)</label>
                        <input type="number" name="interval_max" class="mo-input" min="5" value="300">
                    </div>
                </div>
                <div class="mo-field">
                    <label>Cabang (opsional)</label>
                    <select name="branch_id" class="mo-select">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mo-field">
                    <label>Agen (opsional)</label>
                    <select name="agen_id" class="mo-select">
                        <option value="">Semua Agen</option>
                        @foreach($agens as $agen)
                            <option value="{{ $agen->id }}">{{ $agen->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mo-field">
                    <label>Status Kontak</label>
                    <select name="statuses[]" class="mo-select">
                        <option value="">Semua (kecuali Stop)</option>
                        @foreach($contactStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mo-field">
                    <label>Riwayat Follow-up</label>
                    <select name="followups[]" class="mo-select">
                        <option value="">Semua</option>
                        @foreach($followupBuckets as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mo-field" data-media-wrap>
                    <label>Media (opsional)</label>
                    <input type="file" name="media_file" class="mo-input" data-media-input accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                    <div data-media-info style="display:none;align-items:center;gap:8px;margin-top:6px;">
                        <span data-media-name style="font-size:12px;color:var(--mo-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;"></span>
                        <button type="button" data-media-clear title="Hapus media" style="border:none;background:#fee2e2;color:#b91c1c;width:24px;height:24px;border-radius:50%;cursor:pointer;font-size:14px;line-height:1;flex-shrink:0;">&times;</button>
                    </div>
                    <div class="mo-form-help">Jenis media dikenali otomatis. Biarkan kosong untuk pesan teks.</div>
                </div>
                <div class="mo-field">
                    <label>Template Pesan <span class="req">*</span></label>
                    <textarea name="message" id="mo-broadcast-message" class="mo-textarea" rows="5" required placeholder="Assalamualaikum [nama] ..."></textarea>
                    <div class="mo-form-help">Placeholder [nama], [nomor]. Variasi acak {Halo|Hai}.</div>
                </div>

                <button type="button" class="mo-btn mo-btn-ghost" id="mo-preview-otomatis" style="width:100%;margin-bottom:8px;"><i class="fas fa-eye"></i> Ambil Kontak</button>
                <button type="submit" class="mo-btn mo-btn-primary" style="width:100%;"><i class="fas fa-paper-plane"></i> Mulai Broadcast</button>
            </form>
            <div id="mo-otomatis-preview" style="margin-top:12px;"></div>
        </div>

        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-list-check"></i> Riwayat Broadcast</div>
            <div class="mo-list">
                @forelse($broadcasts as $b)
                    <div class="mo-row">
                        <div class="mo-row-body">
                            <div class="mo-row-title">{{ $b->name }}</div>
                            <div class="mo-row-sub">{{ $b->sent }}/{{ $b->total }} terkirim · {{ $b->failed }} gagal</div>
                            <div style="margin-top:5px;">
                                @php $cls = ['completed'=>'green','running'=>'gold','stopped'=>'gray','failed'=>'red'][$b->status] ?? 'gray'; @endphp
                                <span class="mo-badge {{ $cls }}">{{ $b->statusLabel() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="mo-empty"><i class="fas fa-inbox"></i><p>Belum ada broadcast.</p></div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MANUAL --}}
    <div id="mo-fuwa-manual" hidden>
        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-hand-pointer"></i> Follow-up Manual</div>
            <div class="mo-field">
                <label>Cabang (opsional)</label>
                <select class="mo-select" id="mo-manual-branch">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mo-field">
                <label>Agen (opsional)</label>
                <select class="mo-select" id="mo-manual-agen">
                    <option value="">Semua Agen</option>
                    @foreach($agens as $agen)
                        <option value="{{ $agen->id }}">{{ $agen->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mo-field">
                <label>Status Kontak</label>
                <select class="mo-select" id="mo-manual-status">
                    <option value="">Semua (kecuali Stop)</option>
                    @foreach($contactStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mo-field">
                <label>Riwayat Follow-up</label>
                <select class="mo-select" id="mo-manual-followup">
                    <option value="">Semua</option>
                    @foreach($followupBuckets as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mo-field">
                <label>Jumlah Kontak</label>
                <input type="number" class="mo-input" id="mo-manual-limit" min="1" max="200" value="100">
            </div>
            <div class="mo-field">
                <label>Whatsapp</label>
                <select class="mo-select" id="mo-manual-waapp">
                    <option value="business">WA Bisnis</option>
                    <option value="personal">WA Personal</option>
                </select>
            </div>
            <div class="mo-field">
                <label>Template Pesan</label>
                <textarea id="mo-manual-message" class="mo-textarea" rows="4" placeholder="Assalamualaikum [nama], {Halo|Hai} ..."></textarea>
                <div class="mo-form-help">Placeholder: [nama], [nomor], [nama_agen]. Variasi acak: {Halo|Hai|Assalamualaikum}.</div>
            </div>
            <button type="button" class="mo-btn mo-btn-primary" id="mo-preview-manual" style="width:100%;"><i class="fas fa-eye"></i> Ambil Kontak</button>
            <div id="mo-manual-preview" style="margin-top:12px;"></div>
        </div>
    </div>

    {{-- WARMING --}}
    <div id="mo-fuwa-warming" hidden>
        @if(auth()->user()->isAdmin())
        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-fire"></i> Warming Nomor</div>
            <form method="POST" action="{{ route('mo.whatsapp.warming') }}">
                @csrf
                <div style="display:flex;gap:10px;">
                    <div class="mo-field" style="flex:1;">
                        <label>Pasangan</label>
                        <input type="number" name="amount_pair" class="mo-input" min="1" value="{{ $warmingConfig['amount_pair'] }}">
                    </div>
                    <div class="mo-field" style="flex:1;">
                        <label>Interval</label>
                        <input type="number" name="amount" class="mo-input" min="1" max="50" value="{{ $warmingConfig['amount'] }}">
                    </div>
                </div>
                <div style="display:flex;gap:10px;">
                    <div class="mo-field" style="flex:1;">
                        <label>Jeda Min (dtk)</label>
                        <input type="number" name="interval_min" class="mo-input" min="5" value="{{ $warmingConfig['interval_min'] }}">
                    </div>
                    <div class="mo-field" style="flex:1;">
                        <label>Jeda Maks (dtk)</label>
                        <input type="number" name="interval_max" class="mo-input" min="5" value="{{ $warmingConfig['interval_max'] }}">
                    </div>
                </div>
                <div style="display:flex;gap:10px;">
                    <div class="mo-field" style="flex:1;">
                        <label>Jam Mulai</label>
                        <input type="time" name="start_time" class="mo-input" value="{{ $warmingConfig['start_time'] }}">
                    </div>
                    <div class="mo-field" style="flex:1;">
                        <label>Jam Selesai</label>
                        <input type="time" name="stop_time" class="mo-input" value="{{ $warmingConfig['stop_time'] }}">
                    </div>
                </div>
                @php $weekdays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu']; @endphp
                <div class="mo-field">
                    <label>Hari Pengiriman</label>
                    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                        @foreach($weekdays as $num => $label)
                            <label style="display:flex;align-items:center;gap:5px;font-size:13px;color:var(--mo-text);"><input type="checkbox" name="days[]" value="{{ $num }}" @if(in_array($num, $warmingConfig['days'])) checked @endif> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="mo-field">
                    <label>Template Pesan (satu pesan per baris, dikirim acak)</label>
                    <textarea name="messages" class="mo-textarea" rows="4">{{ $warmingConfig['messages'] }}</textarea>
                </div>
                <label class="mo-switch">
                    <span><span class="lbl">Aktifkan Warming</span></span>
                    <input type="checkbox" name="active" value="1" @if($warmingConfig['active']) checked @endif>
                    <span class="track"></span>
                </label>
                <button type="submit" class="mo-btn mo-btn-primary" style="width:100%;margin-top:10px;"><i class="fas fa-save"></i> Simpan Konfigurasi</button>
            </form>
            <form method="POST" id="mo-warming-run" action="{{ route('mo.whatsapp.warming.run') }}" style="margin-top:8px;">
                @csrf
                <button type="submit" class="mo-btn mo-btn-ghost" style="width:100%;"><i class="fas fa-fire"></i> Jalankan Warming Sekarang</button>
            </form>
        </div>
        @endif

        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-users"></i> Pengguna Warming</div>
            <div class="mo-list">
                @forelse($warmingRecipients as $r)
                    <div class="mo-row">
                        <div class="mo-row-body">
                            <div class="mo-row-title">{{ $r['name'] }}</div>
                            <div class="mo-row-sub">{{ $r['phone'] }} · {{ $r['role'] }}</div>
                            <div class="mo-row-sub">{{ $r['branch'] ?? '-' }}</div>
                            <div style="margin-top:5px;font-size:11px;color:var(--mo-muted);">Kirim: {{ $r['sent'] }} · Terima: {{ $r['received'] }}</div>
                        </div>
                        <div class="mo-row-end" style="flex-direction:column;align-items:flex-end;gap:4px;">
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;color:var(--mo-muted);"><span style="width:9px;height:9px;border-radius:50%;background:{{ $dotColors[$r['ss']] ?? '#cbd5e1' }}"></span> SS</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;color:var(--mo-muted);"><span style="width:9px;height:9px;border-radius:50%;background:{{ $dotColors[$r['cc']] ?? '#cbd5e1' }}"></span> CC</span>
                        </div>
                    </div>
                @empty
                    <div class="mo-empty"><i class="fas fa-users"></i><p>Belum ada pengguna tujuan.</p></div>
                @endforelse
            </div>
        </div>

        @if(auth()->user()->isAdmin())
        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-clock"></i> Status Cron Warming</div>
            @php $cronDot = ['ok' => '#22c55e', 'fail' => '#ef4444', 'empty' => '#cbd5e1'][$cronStatus['status']] ?? '#cbd5e1'; @endphp
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--mo-text);">
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $cronDot }};display:inline-block;flex-shrink:0;"></span>
                <span>Cron terakhir: <strong>{{ $cronStatus['label'] }}</strong>@if($cronStatus['last']) · {{ $cronStatus['last']->format('d M Y H:i') }}@endif</span>
            </div>
        </div>
        @endif
    </div>

    {{-- LOG PESAN --}}
    <div id="mo-fuwa-log" hidden>
        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-clock-rotate-left"></i> Log Pesan</div>
            @php
                $logStatusStyle = [
                    'sent' => 'background:#e7f6ec;color:#15803d;',
                    'pending' => 'background:#fef3e2;color:#b45309;',
                    'failed' => 'background:#fdeaea;color:#b91c1c;',
                ];
            @endphp
            <div class="mo-list">
                @forelse($logs as $log)
                    <div class="mo-row" style="align-items:flex-start;">
                        <div class="mo-row-body">
                            <div class="mo-row-title">{{ $log->contact->name ?? '-' }}</div>
                            <div class="mo-row-sub">{{ $log->phone }}</div>
                            <div style="margin-top:4px;font-size:12px;color:var(--mo-text);">{{ $log->message }}</div>
                            <div style="margin-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span class="mo-badge" style="{{ $logStatusStyle[$log->status] ?? 'background:#eef3f2;color:#64748b;' }}">{{ ucfirst($log->status) }}</span>
                                <span style="font-size:11px;color:var(--mo-muted);">{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                        </div>
                        <div class="mo-row-end">
                            <form method="POST" action="{{ route('mo.whatsapp.log.destroy', $log) }}" onsubmit="return confirm('Hapus log ini?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mo-icon-btn" style="width:36px;height:36px;background:#fdeeec;color:var(--mo-danger);box-shadow:none;" aria-label="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mo-empty"><i class="fas fa-inbox"></i><p>Belum ada log pengiriman.</p></div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var cfg = {
        contacts: '{{ route('mo.whatsapp.contacts') }}',
        manualLog: '{{ route('mo.whatsapp.manual') }}',
        csrf: document.querySelector('meta[name="csrf-token"]').content
    };

    document.querySelectorAll('.mo-segmented-item[data-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.mo-segmented-item[data-tab]').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            ['otomatis', 'manual', 'warming', 'log'].forEach(function (name) {
                var panel = document.getElementById('mo-fuwa-' + name);
                if (panel) panel.hidden = (name !== tab.dataset.tab);
            });
        });
    });

    var scheduleType = document.getElementById('mo-schedule-type');
    if (scheduleType) {
        scheduleType.addEventListener('change', function () {
            document.getElementById('mo-scheduled-group').hidden = scheduleType.value !== 'scheduled';
        });
    }

    // Media upload + remove
    document.querySelectorAll('[data-media-wrap]').forEach(function (wrap) {
        var input = wrap.querySelector('[data-media-input]');
        var info = wrap.querySelector('[data-media-info]');
        var label = wrap.querySelector('[data-media-name]');
        var clear = wrap.querySelector('[data-media-clear]');
        if (!input || !info) return;
        input.addEventListener('change', function () {
            if (input.files && input.files.length) {
                label.textContent = input.files[0].name;
                info.style.display = 'flex';
            } else {
                label.textContent = '';
                info.style.display = 'none';
            }
        });
        if (clear) {
            clear.addEventListener('click', function () {
                input.value = '';
                label.textContent = '';
                info.style.display = 'none';
            });
        }
    });

    function collectList(selector) {
        var out = [];
        Array.prototype.slice.call(document.querySelectorAll(selector)).forEach(function (el) {
            if (el.tagName === 'SELECT') {
                if (el.value !== '') out.push(el.value);
            } else if (el.checked) {
                out.push(el.value);
            }
        });
        return out;
    }

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function render(container, data, manual) {
        if (!data.contacts || data.contacts.length === 0) {
            container.innerHTML = '<div class="mo-empty"><i class="fas fa-circle-info"></i><p>Tidak ada kontak yang cocok.</p></div>';
            return;
        }
        var html = '<div class="mo-form-help" style="margin-bottom:8px;"><strong>' + data.count + '</strong> kontak ditemukan.</div>';
        data.contacts.forEach(function (c) {
            html += '<div class="mo-form-card" style="padding:12px;margin-bottom:8px;">';
            html += '<div style="font-weight:600;font-size:13px;">' + esc(c.name) + ' <span class="mo-badge blue">' + esc(c.followup_count) + 'x</span> <span class="mo-badge gray">' + esc(c.status_label) + '</span></div>';
            html += '<div style="font-size:12px;color:var(--mo-muted);">' + esc(c.phone) + '</div>';
            if (!manual && c.message) html += '<div style="font-size:12px;color:var(--mo-text);margin-top:4px;white-space:pre-wrap;">' + esc(c.message) + '</div>';
            if (manual) {
                html += '<div style="margin-top:8px;">';
                html += '<a href="' + esc(c.wa_link) + '" target="_blank" rel="noopener" class="mo-btn mo-btn-primary mo-open" data-id="' + esc(c.id) + '" data-phone="' + esc(c.phone) + '" data-msg="' + encodeURIComponent(c.message || '') + '" style="width:100%;padding:9px;font-size:13px;"><i class="fab fa-whatsapp"></i> Buka</a>';
                html += '</div>';
            }
            html += '</div>';
        });
        container.innerHTML = html;
    }

    function load(url, payload, container, manual) {
        container.innerHTML = '<div class="mo-form-help"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
        var body = new URLSearchParams();
        Object.keys(payload).forEach(function (k) {
            var v = payload[k];
            if (Array.isArray(v)) { v.forEach(function (x) { body.append(k + '[]', x); }); }
            else if (v !== null && v !== undefined && v !== '') { body.append(k, v); }
        });
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(container, data, manual);
                if (manual) {
                    bindOpenButtons(container);
                }
            })
            .catch(function () { container.innerHTML = '<div class="mo-form-help">Gagal memuat kontak.</div>'; });
    }

    function buildWaUrl(phone, message, app) {
        var digits = String(phone == null ? '' : phone).replace(/[^0-9]/g, '');
        var text = encodeURIComponent(message || '');
        if (/android/i.test(navigator.userAgent)) {
            var pkg = app === 'personal' ? 'com.whatsapp' : 'com.whatsapp.w4b';
            return 'intent://send?phone=' + digits + '&text=' + text + '#Intent;scheme=whatsapp;package=' + pkg + ';end';
        }
        return 'https://wa.me/' + digits + '?text=' + text;
    }

    function markSent(link) {
        link.dataset.sent = '1';
        link.innerHTML = '<i class="fas fa-check-double"></i> Terkirim';
        link.classList.remove('mo-btn-primary');
        link.style.background = '#e2e8f0';
        link.style.color = '#64748b';
        link.style.pointerEvents = 'none';
    }

    function bindOpenButtons(container) {
        container.querySelectorAll('.mo-open').forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (link.dataset.sent === '1') {
                    e.preventDefault();
                    return;
                }
                var app = document.getElementById('mo-manual-waapp');
                link.href = buildWaUrl(link.dataset.phone, decodeURIComponent(link.dataset.msg || ''), app ? app.value : 'business');
                markSent(link);
                var b = new URLSearchParams();
                b.append('contact_id', link.dataset.id);
                b.append('message', decodeURIComponent(link.dataset.msg || ''));
                fetch(cfg.manualLog, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body: b.toString() });
            });
        });
    }

    var po = document.getElementById('mo-preview-otomatis');
    if (po) po.addEventListener('click', function () {
        var form = po.closest('form');
        load(cfg.contacts, {
            branch_id: form.querySelector('[name=branch_id]').value,
            agen_id: form.querySelector('[name=agen_id]').value,
            statuses: collectList('#mo-fuwa-otomatis [name="statuses[]"]'),
            followups: collectList('#mo-fuwa-otomatis [name="followups[]"]'),
            limit: form.querySelector('[name=limit_count]').value,
            message: document.getElementById('mo-broadcast-message').value
        }, document.getElementById('mo-otomatis-preview'), false);
    });

    var pm = document.getElementById('mo-preview-manual');
    if (pm) pm.addEventListener('click', function () {
        load(cfg.contacts, {
            branch_id: document.getElementById('mo-manual-branch').value,
            agen_id: document.getElementById('mo-manual-agen').value,
            statuses: collectList('#mo-manual-status'),
            followups: collectList('#mo-manual-followup'),
            limit: document.getElementById('mo-manual-limit').value,
            message: document.getElementById('mo-manual-message').value
        }, document.getElementById('mo-manual-preview'), true);
    });
})();
</script>
@endpush
