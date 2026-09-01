@extends('mobile.layouts.app')

@section('title', $branch ? 'Edit Cabang' : 'Tambah Cabang')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.branches') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-building" style="color:var(--mo-primary);font-size:20px;"></i> {{ $branch ? 'Edit Cabang' : 'Tambah Cabang' }}</h1>
            <div class="mo-appbar-sub">{{ $branch ? 'Perbarui data cabang' : 'Buat cabang baru' }}</div>
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

    <form method="POST" action="{{ $branch ? route('mo.branch.update', $branch->id) : route('mo.branch.store') }}" class="mo-form">
        @csrf
        @if($branch)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-building"></i> Detail Cabang</h3>

            <div class="mo-field">
                <label for="code">Kode Cabang <span class="req">*</span></label>
                <input type="text" id="code" name="code" class="mo-input" value="{{ old('code', $branch->code ?? '') }}" required placeholder="Contoh: DIY, JTM">
            </div>

            <div class="mo-field">
                <label for="name">Nama Cabang <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="mo-input" value="{{ old('name', $branch->name ?? '') }}" required placeholder="Contoh: Daerah Istimewa Yogyakarta">
            </div>

            <div class="mo-field">
                <label for="city">Kota / Domisili</label>
                <input type="text" id="city" name="city" class="mo-input" value="{{ old('city', $branch->city ?? '') }}" placeholder="Contoh: Yogyakarta">
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-user-tie"></i> Penanggung Jawab</h3>

            <div class="mo-field">
                <label for="supervisor_id">Supervisor</label>
                <select id="supervisor_id" name="supervisor_id" class="mo-select">
                    <option value="">— Pilih Supervisor —</option>
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->id }}" {{ old('supervisor_id', $branch->supervisor_id ?? '') == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
                <div class="mo-form-help">Supervisor akan otomatis terhubung dengan cabang ini.</div>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-chart-line"></i> Target &amp; Status</h3>

            <div class="mo-field">
                <label for="target_amount">Target Donasi Bulanan (Rp) <span class="req">*</span></label>
                <input type="number" id="target_amount" name="target_amount" class="mo-input" value="{{ old('target_amount', $branch->target_amount ?? '') }}" min="0" step="0.01" required placeholder="0">
            </div>

            <div class="mo-switch" style="margin-bottom:0;">
                <div>
                    <div class="lbl">Aktif</div>
                    <div class="sub">Cabang aktif tampil di aplikasi mobile</div>
                </div>
                <input type="checkbox" name="is_active" value="1" id="mo-is-active" {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-is-active"></label>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.branches') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> {{ $branch ? 'Simpan Perubahan' : 'Simpan Cabang' }}</button>
        </div>
    </form>

    @if($branch)
        <div class="mo-form-card" style="margin-top:2px;background:#fff8f7;box-shadow:none;border:1.5px solid #f6d8d4;">
            <h3 class="mo-form-card-title" style="color:var(--mo-danger);"><i class="fas fa-triangle-exclamation"></i> Zona Berbahaya</h3>
            <form method="POST" action="{{ route('mo.branch.destroy', $branch->id) }}" onsubmit="return confirm('Hapus cabang ini? Tindakan tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mo-btn mo-btn-danger mo-btn-block"><i class="fas fa-trash-can"></i> Hapus Cabang</button>
            </form>
        </div>
    @endif
</div>
@endsection
