@extends('layouts.app')

@section('title', 'Edit Donasi')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-hand-holding-dollar"></i> Edit Donasi</h1>
        <p class="subtitle">Donasi #{{ $donation->id }} · {{ $donation->donation_date->format('d M Y') }}</p>
    </div>
    <a href="{{ route('donations.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('donations.update', $donation) }}">
        @csrf
        @method('PUT')
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Nominal (Rp) *</label>
                <input type="number" id="amount" name="amount" value="{{ old('amount', $donation->amount) }}" min="1" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="donation_date">Tanggal Donasi *</label>
                <input type="date" id="donation_date" name="donation_date" value="{{ old('donation_date', $donation->donation_date->toDateString()) }}" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="branch_id">Cabang *</label>
                <select id="branch_id" name="branch_id" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $donation->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="agen_id">Agen *</label>
                <select id="agen_id" name="agen_id" required>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ old('agen_id', $donation->agen_id) == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="program_id">Program *</label>
                <select id="program_id" name="program_id" required>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ old('program_id', $donation->program_id) == $program->id ? 'selected' : '' }}>
                            {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="payment_method">Metode Pembayaran *</label>
                <select id="payment_method" name="payment_method">
                    <option value="cash" {{ old('payment_method', $donation->payment_method) == 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ old('payment_method', $donation->payment_method) == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="qris" {{ old('payment_method', $donation->payment_method) == 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="e-wallet" {{ old('payment_method', $donation->payment_method) == 'e-wallet' ? 'selected' : '' }}>E-Wallet</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="contact_id">Kontak Donatur</label>
            <select id="contact_id" name="contact_id">
                <option value="">— Tanpa kontak terhubung —</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}" {{ old('contact_id', $donation->contact_id) == $contact->id ? 'selected' : '' }}>
                        {{ $contact->name }} ({{ $contact->phone }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="note">Catatan</label>
            <textarea id="note" name="note" rows="3">{{ old('note', $donation->note) }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('donations.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
