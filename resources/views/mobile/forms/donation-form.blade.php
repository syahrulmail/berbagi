@extends('mobile.layouts.app')

@php
    if (! isset($donation) || ! $donation) {
        $donation = new \App\Models\Donation();
    }
    $isEdit = (bool) $donation->id;
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
    @if($errors->any())
        <div class="mo-validation-summary">
            <strong><i class="fas fa-circle-exclamation"></i> Periksa kembali isian:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                <label for="contact_id">Kontak Donatur</label>
                <select id="contact_id" name="contact_id" class="mo-select">
                    <option value="">— Pilih kontak (opsional) —</option>
                    @foreach($contacts as $c)
                        <option value="{{ $c->id }}" {{ old('contact_id', $donation->contact_id ?? '') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}{{ $c->phone ? ' (' . $c->phone . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="mo-form-help">Belum ada kontaknya? Kosongkan dan isi info donatur di bawah.</div>
            </div>

            <div class="mo-field">
                <label for="donor_info">Info Donatur</label>
                <textarea id="donor_info" name="donor_info" class="mo-textarea" rows="2" placeholder="Nama / informasi tambahan bila kontak kosong...">{{ old('donor_info', $donation->donor_info ?? '') }}</textarea>
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
                        <div class="grow">
                            <select name="items[{{ $i }}][program_id]" class="mo-select item-program" required style="margin-bottom:8px;">
                                <option value="">— Pilih program —</option>
                                @foreach($programs as $prog)
                                    <option value="{{ $prog->id }}" data-category="{{ $prog->program_category }}" {{ ($item['program_id'] ?? null) == $prog->id ? 'selected' : '' }}>
                                        {{ $prog->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="text" class="item-category-label" value="{{ old('items.'.$i.'.program_category', $item['program_category'] ?? '') }}" placeholder="Kategori program" style="width:100%;border:none;background:transparent;font-size:11px;color:var(--mo-muted);">
                            <input type="hidden" name="items[{{ $i }}][program_category]" class="item-category-input" value="{{ old('items.'.$i.'.program_category', $item['program_category'] ?? '') }}">
                        </div>
                        <input type="number" name="items[{{ $i }}][amount]" class="amount-inline item-amount" value="{{ old('items.'.$i.'.amount', $item['amount'] ?? '') }}" min="1" step="0.01" required placeholder="Rp">
                        <button type="button" class="mo-item-remove" data-remove-item aria-label="Hapus"><i class="fas fa-xmark"></i></button>
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

        function categoryLabel(code) {
            var found = programsData.find(function (p) { return p.category === code; });
            return found ? found.label : code;
        }

        function recalc() {
            var total = 0;
            itemsWrap.querySelectorAll('[data-item-row]').forEach(function (row) {
                total += parseFloat(row.querySelector('.item-amount').value || 0) || 0;
            });
            totalEl.textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
        }

        function attachRow(row) {
            var sel = row.querySelector('.item-program');
            var label = row.querySelector('.item-category-label');
            var hidden = row.querySelector('.item-category-input');

            sel.addEventListener('change', function () {
                var opt = sel.options[sel.selectedIndex];
                var cat = opt ? opt.getAttribute('data-category') : '';
                label.value = categoryLabel(cat);
                hidden.value = cat || '';
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

            var opts = '<option value="">— Pilih program —</option>';
            programsData.forEach(function (p) {
                opts += '<option value="' + p.id + '" data-category="' + p.category + '">' + p.name.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>';
            });

            row.innerHTML =
                '<div class="grow">' +
                    '<select name="items[' + itemIndex + '][program_id]" class="mo-select item-program" required style="margin-bottom:8px;">' + opts + '</select>' +
                    '<input type="text" class="item-category-label" value="" placeholder="Kategori program" style="width:100%;border:none;background:transparent;font-size:11px;color:var(--mo-muted);">' +
                    '<input type="hidden" name="items[' + itemIndex + '][program_category]" class="item-category-input" value="">' +
                '</div>' +
                '<input type="number" name="items[' + itemIndex + '][amount]" class="amount-inline item-amount" min="1" step="0.01" required placeholder="Rp">' +
                '<button type="button" class="mo-item-remove" data-remove-item aria-label="Hapus"><i class="fas fa-xmark"></i></button>';

            itemIndex++;
            itemsWrap.appendChild(row);
            attachRow(row);
            recalc();
        });

        itemsWrap.querySelectorAll('[data-item-row]').forEach(attachRow);
        recalc();

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
