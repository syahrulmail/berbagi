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
    <form method="POST" action="{{ route('donations.update', $donation) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-frame">
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
                    <label for="agen_id">Agent *</label>
                    <select id="agen_id" name="agen_id" required>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id }}" {{ old('agen_id', $donation->agen_id) == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="donation_date">Tanggal Donasi *</label>
            <input type="date" id="donation_date" name="donation_date" value="{{ old('donation_date', $donation->donation_date->toDateString()) }}" required>
        </div>

        <div class="form-group">
            <label for="contact_id">Kontak Donatur *</label>
            @php
                $contactSelected = old('contact_id', $donation->contact_id);
                $contactModel = $contacts->firstWhere('id', $contactSelected);
                $contactLabel = $contactModel ? $contactModel->name . ($contactModel->phone ? ' (' . $contactModel->phone . ')' : '') : '';
            @endphp
            @include('partials.searchable-select', [
                'name' => 'contact_id',
                'options' => $contacts,
                'valueField' => 'id',
                'labelField' => 'name',
                'suffixField' => 'phone',
                'selectedValue' => $contactSelected,
                'selectedLabel' => $contactLabel,
                'placeholder' => 'Ketik nama atau nomor HP donatur...',
                'allowEmpty' => false,
            ])
            @include('partials.contact-quick-add')
        </div>

        <div class="form-group">
            <label for="donor_info">Info Donatur</label>
            <textarea id="donor_info" name="donor_info" rows="3" placeholder="Informasi tambahan tentang donatur...">{{ old('donor_info', $donation->donor_info) }}</textarea>
        </div>

        @php
            $editItems = $donation->items->isEmpty() ? collect([null]) : $donation->items;
        @endphp
        <div class="form-group">
            <label>Program Donasi *</label>
            <div class="donation-items-card">
                <div id="donation-items">
                    @foreach($editItems as $itemIndex => $item)
                        @include('partials.donation-item-row', [
                            'index' => $itemIndex,
                            'rowProgramId' => old('items.' . $itemIndex . '.program_id', $item ? $item->program_id : ''),
                            'rowCategory' => old('items.' . $itemIndex . '.program_category', $item ? $item->program_category : ''),
                            'rowAmount' => old('items.' . $itemIndex . '.amount', $item ? $item->amount : ''),
                        ])
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline btn-sm" id="add-item-btn"><i class="fas fa-plus"></i> Tambah Program</button>
                <div class="donation-total">Total Donasi: <strong id="donation-total">Rp 0</strong></div>
            </div>
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

        <div class="form-group">
            <label for="note">Catatan</label>
            <textarea id="note" name="note" rows="3">{{ old('note', $donation->note) }}</textarea>
        </div>

        <div class="form-group">
            <label for="payment_proof">Bukti Pembayaran</label>
            <input type="file" id="payment_proof" name="payment_proof"
                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                   data-proof-input data-proof-preview="#proof-preview">
            @if($donation->payment_proof)
                <div>
                    <img id="proof-preview" src="{{ asset_photo_url($donation->payment_proof) }}" alt="Bukti Pembayaran" class="proof-preview">
                </div>
                <label class="proof-remove-label">
                    <input type="checkbox" name="remove_payment_proof" value="1"> Hapus bukti pembayaran
                </label>
            @else
                <div>
                    <img id="proof-preview" src="" alt="Preview bukti pembayaran" class="proof-preview" style="display:none;">
                </div>
            @endif
            <small style="color:var(--muted);">Format JPG, PNG, GIF, WebP. Maks 5MB.</small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('donations.index') }}" class="btn">Batal</a>
        </div>
    </form>
    @include('partials.contact-quick-add-modal')
</div>
@endsection

@push('scripts')
<script src="{{ assetv('js/donation-form.js') }}"></script>
@endpush
