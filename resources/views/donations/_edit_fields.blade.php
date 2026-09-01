{{--
    Field form edit donasi untuk dimuat di dalam modal rincian (donations.index).
    Parameters:
    - $donation : model Donation
    - $branches, $programs, $agents, $contacts (dipass oleh DonationController@editFields)
    Form tidak memakai <form> global; AJAX memakai FormData dari container #donation-modal-form.
--}}
<form id="donation-modal-form">
    <div class="form-frame">
        <div class="form-row">
            <div class="form-group">
                <label for="branch_id">Cabang *</label>
                <select id="branch_id" name="branch_id" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $donation->branch_id == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="agen_id">Agent *</label>
                <select id="agen_id" name="agen_id" required>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id }}" {{ $donation->agen_id == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="modal_donation_date">Tanggal Donasi *</label>
        <input type="date" id="modal_donation_date" name="donation_date" value="{{ $donation->donation_date->toDateString() }}" required>
    </div>

    <div class="form-group">
        <label for="modal_contact_id">Kontak Donatur *</label>
        @php
            $contactLabel = $donation->contact ? $donation->contact->name . ($donation->contact->phone ? ' (' . $donation->contact->phone . ')' : '') : '';
        @endphp
        @include('partials.searchable-select', [
            'name' => 'contact_id',
            'options' => $contacts,
            'valueField' => 'id',
            'labelField' => 'name',
            'suffixField' => 'phone',
            'selectedValue' => $donation->contact_id,
            'selectedLabel' => $contactLabel,
            'placeholder' => 'Ketik nama atau nomor HP donatur...',
            'allowEmpty' => false,
        ])
    </div>

    <div class="form-group">
        <label for="modal_donor_info">Info Donatur</label>
        <textarea id="modal_donor_info" name="donor_info" rows="3" placeholder="Informasi tambahan tentang donatur...">{{ $donation->donor_info }}</textarea>
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
                        'rowProgramId' => $item ? $item->program_id : '',
                        'rowCategory' => $item ? $item->program_category : '',
                        'rowAmount' => $item ? $item->amount : '',
                    ])
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="add-item-btn"><i class="fas fa-plus"></i> Tambah Program</button>
            <div class="donation-total">Total Donasi: <strong id="donation-total">Rp 0</strong></div>
        </div>
    </div>

    <div class="form-group">
        <label for="modal_payment_method">Metode Pembayaran *</label>
        <select id="modal_payment_method" name="payment_method">
            <option value="cash" {{ $donation->payment_method == 'cash' ? 'selected' : '' }}>Tunai</option>
            <option value="transfer" {{ $donation->payment_method == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
            <option value="qris" {{ $donation->payment_method == 'qris' ? 'selected' : '' }}>QRIS</option>
            <option value="e-wallet" {{ $donation->payment_method == 'e-wallet' ? 'selected' : '' }}>E-Wallet</option>
        </select>
    </div>

    <div class="form-group">
        <label for="modal_note">Catatan</label>
        <textarea id="modal_note" name="note" rows="3">{{ $donation->note }}</textarea>
    </div>

    <div class="form-group">
        <label for="modal_payment_proof">Bukti Pembayaran</label>
        <input type="file" id="modal_payment_proof" name="payment_proof"
               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
               data-proof-input data-proof-preview="#modal-proof-preview">
        @if($donation->payment_proof)
            <div>
                <img id="modal-proof-preview" src="{{ asset_photo_url($donation->payment_proof) }}" alt="Bukti Pembayaran" class="proof-preview">
            </div>
            <label class="proof-remove-label">
                <input type="checkbox" name="remove_payment_proof" value="1"> Hapus bukti pembayaran
            </label>
        @else
            <div>
                <img id="modal-proof-preview" src="" alt="Preview bukti pembayaran" class="proof-preview" style="display:none;">
            </div>
        @endif
        <small style="color:var(--muted);">Format JPG, PNG, GIF, WebP. Maks 5MB.</small>
    </div>
</form>
