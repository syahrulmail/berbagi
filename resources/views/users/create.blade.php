@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-user-plus"></i> Tambah Pengguna</h1>
        <p class="subtitle">Buat akun baru untuk tim BWA.</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Nama Lengkap *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password *</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="agen" {{ old('role') == 'agen' ? 'selected' : '' }}>Agen / Freelancer</option>
                    <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor / TL</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin Super</option>
                    <option value="donatur" {{ old('role') == 'donatur' ? 'selected' : '' }}>Donatur (Publik)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id">
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="phone">No. WhatsApp</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="628xxxxxxx">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('users.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
