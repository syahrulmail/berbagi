@extends('mobile.layouts.app')

@php
    if (! isset($donation) || ! $donation) {
        $donation = new \App\Models\Donation();
    }
    $isEdit = (bool) $donation->id;

    $selectedContactId = old('contact_id', $donation->contact_id ?? '');
    $selectedContactLabel = $selectedContact ? $selectedContact->name . ($selectedContact->phone ? ' (' . $selectedContact->phone . ')' : '') : '';
    $programNames = $programs->pluck('name', 'id');
@endphp

@section('title', $isEdit ? 'Edit Donasi' : 'Catat Donasi')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.donations') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-hand-holding-dollar" style="color:var(--mo-primary);font-size:20px;"></i> {{ $isEdit ? 'Edit Donasi' : 'Catat Donasi' }}</h1>
            <div class="mo-appbar-sub">{{ $isEdit ? 'Perbarui data donasi' : 'Input donasi wakaf baru' }}</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ $isEdit ? route('mo.donation.update', $donation->id) : route('mo.donation.store') }}" enctype="multipart/form-data" class="mo-form" id="mo-donation-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-shop"></i> Asal Donasi</h3>

            <div class="mo-field">
                <label for="branch_id">Cabang <span class="req">*</span></label>
                <select id="branch_id" name="branch_id" class="mo-select" required {{ $user->isAgen() ? 'disabled' : '' }}>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $donation->branch_id ?? $user->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @if($user->isAgen())
                    <input type="hidden" name="branch_id" value="{{ old('branch_id', $donation->branch_id ?? $user->branch_id) }}">
                @endif
            </div>

            <div class="mo-field">
                <label for="agen_id">Agen / Penerima <span class="req">*</span></label>
                <select id="agen_id" name="agen_id" class="mo-select" required {{ $user->isAgen() ? 'disabled' : '' }}>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id }}" {{ old('agen_id', $donation->agen_id ?? $user->id) == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
                @if($user->isAgen())
                    <input type="hidden" name="agen_id" value="{{ $user->id }}">
                @endif
            </div>

            <div class="mo-field">
                <label for="donation_date">Tanggal Donasi <span class="req">*</span></label>
                <input type="date" id="donation_date" name="donation_date" class="mo-input"
                       value="{{ old('donation_date', $donation->donation_date ? $donation->donation_date->toDateString() : now()->toDateString()) }}" required>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-user"></i> Donatur</h3>

            <div class="mo-field">
                <label for="contact_search">Kontak Donatur <span class="req">*</span></label>
                <div class="mo-ac" id="mo-contact-ac">
                    <input type="text" class="mo-input mo-ac-input" id="contact_search" placeholder="Ketik nama atau nomor WA..." autocomplete="off" value="{{ $selectedContactLabel }}">
                    <input type="hidden" name="contact_id" id="contact_id" value="{{ $selectedContactId }}">
                    <div class="mo-ac-list" hidden></div>
                </div>
                <button type="button" class="mo-ac-add" id="mo-add-contact"><i class="fas fa-plus"></i> Tambah kontak baru</button>
            </div>

            <div class="mo-field">
                <label for="donor_info">Info Donatur</label>
                <textarea id="donor_info" name="donor_info" class="mo-textarea" rows="2">{{ old('donor_info', $donation->donor_info ?? '') }}</textarea>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-file-invoice-dollar"></i> Program Donasi <span class="req">*</span></h3>

            <div id="mo-donation-items">
                @php
                    $items = $donation && $donation->items->count() ? $donation->items : [[
                        'program_id' => old('items.0.program_id'),
                        'program_category' => old('items.0.program_category'),
                        'amount' => old('items.0.amount'),
                    ]];
                @endphp
                @foreach($items as $i => $item)
                    <div class="mo-item-row" data-item-row>
                        <div class="mo-item-main">
                            <div class="mo-ac mo-program-ac">
                                <input type="text" class="mo-input mo-ac-input item-program-search" placeholder="Ketik nama program..." autocomplete="off" value="{{ ($item['program_id'] ?? null) ? ($programNames[$item['program_id']] ?? '') : '' }}">
                                <input type="hidden" name="items[{{ $i }}][program_id]" class="item-program" value="{{ $item['program_id'] ?? '' }}">
                                <div class="mo-ac-list" hidden></div>
                            </div>
                            <input type="text" class="item-category-label" value="{{ old('items.'.$i.'.program_category', $item['program_category'] ?? '') }}" placeholder="Kategori program" readonly tabindex="-1">
                            <input type="hidden" name="items[{{ $i }}][program_category]" class="item-category-input" value="{{ old('items.'.$i.'.program_category', $item['program_category'] ?? '') }}">
                        </div>
                        <div class="mo-item-bottom">
                            <input type="number" name="items[{{ $i }}][amount]" class="amount-inline item-amount" value="{{ old('items.'.$i.'.amount', $item['amount'] ?? '') }}" min="1" step="0.01" required placeholder="Rp">
                            <button type="button" class="mo-item-remove" data-remove-item aria-label="Hapus"><i class="fas fa-xmark"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="mo-add-item" id="mo-add-item"><i class="fas fa-plus"></i> Tambah Program</button>

            <div class="mo-donation-total">
                <span>Total Donasi</span>
                <strong id="mo-donation-total">Rp 0</strong>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-money-bill-wave"></i> Pembayaran</h3>

            <div class="mo-field">
                <label for="payment_date">Tanggal Pembayaran</label>
                <input type="date" id="payment_date" name="payment_date" class="mo-input"
                       value="{{ old('payment_date', $donation->payment_date ? $donation->payment_date->toDateString() : now()->toDateString()) }}">
            </div>

            <div class="mo-field">
                <label for="payment_method">Metode Pembayaran <span class="req">*</span></label>
                <select id="payment_method" name="payment_method" class="mo-select">
                    <option value="cash" {{ old('payment_method', $donation->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ old('payment_method', $donation->payment_method ?? '') == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="qris" {{ old('payment_method', $donation->payment_method ?? '') == 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="e-wallet" {{ old('payment_method', $donation->payment_method ?? '') == 'e-wallet' ? 'selected' : '' }}>E-Wallet</option>
                </select>
            </div>

            <div class="mo-field">
                <label>Bukti Pembayaran</label>
                <div class="mo-file-input">
                    <i class="fas fa-cloud-arrow-up"></i>
                    Pilih foto bukti pembayaran
                    <input type="file" name="payment_proof" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" data-proof-input>
                </div>
                <img src="" alt="" class="mo-thumb" data-proof-preview>
                @if($isEdit && $donation->payment_proof)
                    <div style="margin-top:8px;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--mo-muted);">
                        <i class="fas fa-paperclip"></i> Bukti lama tersimpan.
                        <label style="margin-left:auto;display:flex;align-items:center;gap:5px;color:var(--mo-danger);">
                            <input type="checkbox" name="remove_payment_proof" value="1"> Hapus
                        </label>
                    </div>
                @endif
                <div class="mo-form-help">JPG, PNG, GIF, WebP. Maks 5MB.</div>
            </div>

            <div class="mo-field">
                <label for="note">Catatan</label>
                <textarea id="note" name="note" class="mo-textarea" rows="2" placeholder="Catatan donasi...">{{ old('note', $donation->note ?? '') }}</textarea>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.donations') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Donasi' }}</button>
        </div>
    </form>

    @if($isEdit)
        <div class="mo-form-card" style="margin-top:2px;background:#fff8f7;box-shadow:none;border:1.5px solid #f6d8d4;">
            <h3 class="mo-form-card-title" style="color:var(--mo-danger);"><i class="fas fa-triangle-exclamation"></i> Zona Berbahaya</h3>
            <form method="POST" action="{{ route('mo.donation.destroy', $donation->id) }}" onsubmit="return confirm('Hapus donasi ini? Tindakan tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mo-btn mo-btn-danger mo-btn-block"><i class="fas fa-trash-can"></i> Hapus Donasi</button>
            </form>
        </div>
    @endif
</div>
@endsection

@section('sheets')
<div class="mo-sheet-backdrop" data-for="mo-quick-contact"></div>
<div class="mo-sheet" id="mo-quick-contact" aria-hidden="true">
    <div class="mo-sheet-handle"></div>
    <div class="mo-sheet-head">
        <h3 class="mo-sheet-title"><i class="fas fa-user-plus" style="color:var(--mo-primary);margin-right:6px;"></i>Kontak Baru</h3>
        <button type="button" class="mo-sheet-close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="mo-sheet-body">
        <form id="mo-quick-contact-form" autocomplete="off">
            <div class="mo-field">
                <label for="mo-qc-name">Nama <span class="req">*</span></label>
                <input type="text" id="mo-qc-name" name="name" class="mo-input" required>
            </div>
            <div class="mo-field">
                <label for="mo-qc-phone">No. WhatsApp <span class="req">*</span></label>
                <input type="tel" id="mo-qc-phone" name="phone" class="mo-input" required placeholder="0812xxxxxxx">
            </div>
            <div class="mo-field">
                <label for="mo-qc-status">Status</label>
                <select id="mo-qc-status" name="status" class="mo-select">
                    <option value="prospect">Prospek</option>
                    <option value="contacted">Simpan</option>
                    <option value="donated">Wakif</option>
                    <option value="churned">Stop</option>
                </select>
            </div>
            <div class="mo-form-error" id="mo-quick-contact-error" style="display:none;"></div>
            <button type="submit" class="mo-btn mo-btn-primary mo-btn-block" style="margin-top:6px;"><i class="fas fa-save"></i> Simpan Kontak</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@php
    $programsData = $programs->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->program_category,
            'label' => $p->category_label,
        ];
    })->values()->all();
@endphp
<script>
    (function () {
        'use strict';

        var itemsWrap = document.getElementById('mo-donation-items');
        var addBtn = document.getElementById('mo-add-item');
        var totalEl = document.getElementById('mo-donation-total');
        var itemIndex = itemsWrap.querySelectorAll('[data-item-row]').length;
        var programsData = @json($programsData);
        var contactSearchUrl = '{{ route('mo.api.contact-search') }}';

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function showToast(msg, isError) {
            var stack = document.createElement('div');
            stack.className = 'mo-flash-stack';
            stack.innerHTML = '<div class="mo-toast ' + (isError ? 'mo-toast--error' : 'mo-toast--success') + '">' +
                '<i class="fas ' + (isError ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span></span></div>';
            stack.querySelector('span').textContent = msg;
            document.querySelector('.mo-app').appendChild(stack);
            setTimeout(function () { stack.remove(); }, 3200);
        }

        var programItems = programsData.map(function (p) {
            return { id: p.id, label: p.name, search: p.name, meta: p.label || '', category: p.category || '' };
        });

        function fetchContacts(q, cb) {
            fetch(contactSearchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); })
              .then(function (data) {
                  cb(((data && data.contacts) || []).map(function (c) {
                      return { id: c.id, label: c.label, search: c.name, meta: c.phone || '' };
                  }));
              })
              .catch(function () { cb([]); });
        }

        function initAutoComplete(ac, opts) {
            opts = opts || {};
            var input = ac.querySelector('.mo-ac-input');
            var list = ac.querySelector('.mo-ac-list');
            var hidden = ac.querySelector('input[type="hidden"]');
            var seq = 0;

            function norm(s) { return String(s == null ? '' : s).toLowerCase().replace(/[\s+\-().]/g, ''); }
            function digits(s) { return String(s == null ? '' : s).replace(/[^0-9]/g, ''); }

            function close() { list.hidden = true; list.innerHTML = ''; }

            function choose(item) {
                if (hidden) hidden.value = item.id;
                input.value = item.label;
                close();
                if (opts.onSelect) opts.onSelect(item);
            }

            function paint(res) {
                list.__items = res;
                if (!res.length) {
                    list.innerHTML = '<div class="mo-ac-empty">Tidak ditemukan</div>';
                    list.hidden = false;
                    return;
                }
                list.innerHTML = res.map(function (it) {
                    return '<button type="button" class="mo-ac-item" data-id="' + esc(it.id) + '">' +
                        '<span class="mo-ac-name">' + esc(it.search) + '</span>' +
                        (it.meta ? '<span class="mo-ac-meta">' + esc(it.meta) + '</span>' : '') +
                        '</button>';
                }).join('');
                list.hidden = false;
            }

            function render(q) {
                if (opts.remote) {
                    var myseq = ++seq;
                    clearTimeout(list.__timer);
                    list.__timer = setTimeout(function () {
                        opts.remote(q, function (res) {
                            if (myseq !== seq) return;
                            paint(res || []);
                        });
                    }, 200);
                    return;
                }

                var items = opts.items || [];
                var nq = norm(q);
                var dq = digits(q);
                paint(items.filter(function (it) {
                    if (!nq) return true;
                    if (norm(it.search).indexOf(nq) !== -1) return true;
                    if (it.meta && dq && digits(it.meta).indexOf(dq) !== -1) return true;
                    return false;
                }).slice(0, 50));
            }

            input.addEventListener('focus', function () { render(''); });
            input.addEventListener('input', function () {
                if (hidden) hidden.value = '';
                render(input.value);
            });
            list.addEventListener('mousedown', function (e) {
                var btn = e.target.closest('.mo-ac-item');
                if (!btn) return;
                e.preventDefault();
                var id = btn.getAttribute('data-id');
                var found = null;
                (list.__items || []).some(function (it) {
                    if (String(it.id) === String(id)) { found = it; return true; }
                    return false;
                });
                if (found) choose(found);
            });
        }

        function recalc() {
            var total = 0;
            itemsWrap.querySelectorAll('[data-item-row]').forEach(function (row) {
                total += parseFloat(row.querySelector('.item-amount').value || 0) || 0;
            });
            totalEl.textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
        }

        function attachRow(row) {
            var ac = row.querySelector('.mo-program-ac');
            var label = row.querySelector('.item-category-label');
            var catHidden = row.querySelector('.item-category-input');

            initAutoComplete(ac, {
                items: programItems,
                onSelect: function (item) {
                    label.value = item.meta || '';
                    catHidden.value = item.category || '';
                }
            });

            row.querySelector('.item-amount').addEventListener('input', recalc);

            row.querySelector('[data-remove-item]').addEventListener('click', function () {
                if (itemsWrap.querySelectorAll('[data-item-row]').length <= 1) return;
                row.remove();
                recalc();
            });
        }

        addBtn.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'mo-item-row';
            row.setAttribute('data-item-row', '');

            row.innerHTML =
                '<div class="mo-item-main">' +
                    '<div class="mo-ac mo-program-ac">' +
                        '<input type="text" class="mo-input mo-ac-input item-program-search" placeholder="Ketik nama program..." autocomplete="off">' +
                        '<input type="hidden" name="items[' + itemIndex + '][program_id]" class="item-program" value="">' +
                        '<div class="mo-ac-list" hidden></div>' +
                    '</div>' +
                    '<input type="text" class="item-category-label" value="" placeholder="Kategori program" readonly tabindex="-1">' +
                    '<input type="hidden" name="items[' + itemIndex + '][program_category]" class="item-category-input" value="">' +
                '</div>' +
                '<div class="mo-item-bottom">' +
                    '<input type="number" name="items[' + itemIndex + '][amount]" class="amount-inline item-amount" min="1" step="0.01" required placeholder="Rp">' +
                    '<button type="button" class="mo-item-remove" data-remove-item aria-label="Hapus"><i class="fas fa-xmark"></i></button>' +
                '</div>';

            itemIndex++;
            itemsWrap.appendChild(row);
            attachRow(row);
            recalc();
        });

        itemsWrap.querySelectorAll('[data-item-row]').forEach(attachRow);
        recalc();

        /* ---------- Kontak Donatur ---------- */
        var contactAc = document.getElementById('mo-contact-ac');
        if (contactAc) {
            initAutoComplete(contactAc, { remote: fetchContacts });
        }

        document.addEventListener('click', function (e) {
            document.querySelectorAll('.mo-form .mo-ac').forEach(function (ac) {
                if (!ac.contains(e.target)) {
                    var l = ac.querySelector('.mo-ac-list');
                    if (l) l.hidden = true;
                }
            });
        });

        /* ---------- Modal kontak baru ---------- */
        var addContactBtn = document.getElementById('mo-add-contact');
        var quickForm = document.getElementById('mo-quick-contact-form');
        var quickError = document.getElementById('mo-quick-contact-error');

        if (addContactBtn) {
            addContactBtn.addEventListener('click', function () {
                if (quickError) { quickError.style.display = 'none'; quickError.textContent = ''; }
                if (quickForm) quickForm.reset();
                window.MoApp.sheets.open('mo-quick-contact');
            });
        }

        if (quickForm) {
            quickForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var submitBtn = quickForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;

                fetch('{{ route('contacts.quick') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.MoApp.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: new FormData(quickForm)
                }).then(function (r) {
                    return r.json().then(function (data) { return { ok: r.ok, data: data }; });
                }).then(function (res) {
                    submitBtn.disabled = false;
                    if (!res.ok || !res.data || !res.data.success) {
                        var msg = (res.data && res.data.message) || 'Gagal menyimpan kontak.';
                        if (res.data && res.data.errors) {
                            var keys = Object.keys(res.data.errors);
                            if (keys.length) msg = res.data.errors[keys[0]][0];
                        }
                        if (quickError) { quickError.textContent = msg; quickError.style.display = 'flex'; }
                        return;
                    }
                    var c = res.data.contact;
                    var label = c.name + (c.phone ? ' (' + c.phone + ')' : '');
                    var ch = document.getElementById('contact_id');
                    var ci = document.getElementById('contact_search');
                    if (ch) ch.value = c.id;
                    if (ci) ci.value = label;
                    window.MoApp.sheets.close('mo-quick-contact');
                    showToast('Kontak ' + c.name + ' ditambahkan.');
                }).catch(function () {
                    submitBtn.disabled = false;
                    if (quickError) { quickError.textContent = 'Terjadi kesalahan. Coba lagi.'; quickError.style.display = 'flex'; }
                });
            });
        }

        /* ---------- Validasi wajib ---------- */
        var donationForm = document.getElementById('mo-donation-form');
        if (donationForm) {
            donationForm.addEventListener('submit', function (e) {
                var contactId = document.getElementById('contact_id');
                if (!contactId || !contactId.value) {
                    e.preventDefault();
                    showToast('Kontak donatur wajib dipilih.', true);
                    var ci = document.getElementById('contact_search');
                    if (ci) ci.focus();
                    return;
                }
                var missing = false;
                itemsWrap.querySelectorAll('[data-item-row]').forEach(function (row) {
                    if (!row.querySelector('.item-program').value) missing = true;
                });
                if (missing) {
                    e.preventDefault();
                    showToast('Setiap baris program wajib memilih program.', true);
                }
            });
        }

        var proofInput = document.querySelector('[data-proof-input]');
        var proofPreview = document.querySelector('[data-proof-preview]');
        if (proofInput) {
            proofInput.addEventListener('change', function () {
                var file = proofInput.files[0];
                if (!file) { proofPreview.style.display = 'none'; return; }
                var reader = new FileReader();
                reader.onload = function (e) {
                    proofPreview.src = e.target.result;
                    proofPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }
    })();
</script>
@endpush
