@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-user-pen"></i> Edit Pengguna</h1>
        <p class="subtitle">{{ $user->name }}</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Nama Lengkap *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
            </div>
            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="agen" {{ old('role', $user->role) == 'agen' ? 'selected' : '' }}>Agen / Freelancer</option>
                    <option value="supervisor" {{ old('role', $user->role) == 'supervisor' ? 'selected' : '' }}>Supervisor / TL</option>
                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin Super</option>
                    <option value="donatur" {{ old('role', $user->role) == 'donatur' ? 'selected' : '' }}>Donatur (Publik)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id">
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="phone">No. WhatsApp</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('users.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
