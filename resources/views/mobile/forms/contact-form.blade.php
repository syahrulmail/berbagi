@extends('mobile.layouts.app')

@section('title', $contact ? 'Edit Kontak' : 'Tambah Kontak')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.contacts') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-address-book" style="color:var(--mo-primary);font-size:20px;"></i> {{ $contact ? 'Edit Kontak' : 'Tambah Kontak' }}</h1>
            <div class="mo-appbar-sub">{{ $contact ? 'Perbarui data kontak' : 'Tambahkan calon donatur baru' }}</div>
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

    <form method="POST" action="{{ $contact ? route('mo.contact.update', $contact->id) : route('mo.contact.store') }}" class="mo-form">
        @csrf
        @if($contact)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-user"></i> Identitas</h3>

            <div class="mo-field">
                <label for="name">Nama <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="mo-input" value="{{ old('name', $contact->name ?? '') }}" required placeholder="Nama lengkap / panggilan">
            </div>

            <div class="mo-field">
                <label for="phone">No. WhatsApp <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone" class="mo-input" value="{{ old('phone', $contact->phone ?? '') }}" required placeholder="628xxxxxxx">
                <div class="mo-form-help">Gunakan angka 10-15 digit, boleh diawali 0, 62, atau +62.</div>
            </div>

            <div class="mo-field">
                <label for="status">Status <span class="req">*</span></label>
                <select id="status" name="status" class="mo-select">
                    <option value="prospect" {{ old('status', $contact->status ?? 'prospect') == 'prospect' ? 'selected' : '' }}>Prospek</option>
                    <option value="contacted" {{ old('status', $contact->status ?? '') == 'contacted' ? 'selected' : '' }}>Simpan</option>
                    <option value="donated" {{ old('status', $contact->status ?? '') == 'donated' ? 'selected' : '' }}>Wakif</option>
                    <option value="churned" {{ old('status', $contact->status ?? '') == 'churned' ? 'selected' : '' }}>Stop</option>
                </select>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-shop"></i> Penanggung Jawab</h3>

            <div class="mo-field">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id" class="mo-select" {{ $user->isAgen() ? 'disabled' : '' }}>
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $contact->branch_id ?? $user->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @if($user->isAgen())
                    <input type="hidden" name="branch_id" value="{{ old('branch_id', $contact->branch_id ?? $user->branch_id) }}">
                @endif
            </div>

            <div class="mo-field">
                <label for="agen_id">Agen</label>
                <select id="agen_id" name="agen_id" class="mo-select" {{ $user->isAgen() ? 'disabled' : '' }}>
                    <option value="">— Pilih Agen —</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id }}" {{ old('agen_id', $contact->agen_id ?? ($user->isAgen() ? $user->id : '')) == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
                @if($user->isAgen())
                    <input type="hidden" name="agen_id" value="{{ $user->id }}">
                @endif
            </div>

            <div class="mo-field" style="margin-bottom:0;">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" class="mo-textarea" rows="3" placeholder="Catatan tentang kontak ini...">{{ old('notes', $contact->notes ?? '') }}</textarea>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.contacts') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> {{ $contact ? 'Simpan Perubahan' : 'Simpan Kontak' }}</button>
        </div>
    </form>

    @if($contact)
        <div class="mo-form-card" style="margin-top:2px;background:#fff8f7;box-shadow:none;border:1.5px solid #f6d8d4;">
            <h3 class="mo-form-card-title" style="color:var(--mo-danger);"><i class="fas fa-triangle-exclamation"></i> Zona Berbahaya</h3>
            <form method="POST" action="{{ route('mo.contact.destroy', $contact->id) }}" onsubmit="return confirm('Hapus kontak ini? Tindakan tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mo-btn mo-btn-danger mo-btn-block"><i class="fas fa-trash-can"></i> Hapus Kontak</button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        'use strict';
        var branchSel = document.getElementById('branch_id');
        var agentSel = document.getElementById('agen_id');
        if (branchSel && agentSel && !branchSel.disabled) {
            var agentOptions = Array.from(agentSel.options).slice(1);
            var filterAgents = function () {
                var b = branchSel.value;
                var anyVisible = false;
                agentOptions.forEach(function (o) {
                    var show = !b || o.getAttribute('data-branch') === b;
                    o.style.display = show ? '' : 'none';
                    if (show) anyVisible = true;
                });
                if (agentSel.value && !agentOptions.some(function (o) { return o.value === agentSel.value && o.style.display !== 'none'; })) {
                    agentSel.value = '';
                }
                if (!anyVisible) agentSel.value = '';
            };
            branchSel.addEventListener('change', filterAgents);
            filterAgents();
        }
    })();
</script>
@endpush
