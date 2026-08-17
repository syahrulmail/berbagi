@extends('layouts.app')

@section('title', 'Tambah Cabang')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-building"></i> Tambah Cabang</h1>
        <p class="subtitle">Tambahkan cabang BWA baru.</p>
    </div>
    <a href="{{ route('branches.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('branches.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label for="code">Kode Cabang *</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" placeholder="BWA-31" required>
            </div>
            <div class="form-group">
                <label for="city">Kota</label>
                <input type="text" id="city" name="city" value="{{ old('city') }}">
            </div>
        </div>
        <div class="form-group">
            <label for="name">Nama Cabang *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Cabang Surabaya" required>
        </div>
        <div class="form-group">
            <label for="supervisor_id">Supervisor</label>
            <select id="supervisor_id" name="supervisor_id">
                <option value="">— Pilih Supervisor —</option>
                @foreach($supervisors as $supervisor)
                    <option value="{{ $supervisor->id }}" {{ old('supervisor_id') == $supervisor->id ? 'selected' : '' }}>
                        {{ $supervisor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="target_amount">Target Donasi (Rp) *</label>
            <input type="number" id="target_amount" name="target_amount" value="{{ old('target_amount', 50000000) }}" min="0" step="0.01" required>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('branches.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
