{{--
    Modal Rincian Donasi untuk halaman Manajemen Donasi.
    Memuat data rincian via AJAX (donations.detail) dan form edit via AJAX (donations.edit-fields).
--}}
@php
    $donationModalConfig = [
        'detailUrl' => route('donations.detail', ['donation' => '__ID__']),
        'editFieldsUrl' => route('donations.edit-fields', ['donation' => '__ID__']),
        'updateUrl' => route('donations.update', ['donation' => '__ID__']),
    ];
@endphp
<div class="modal-backdrop" id="donation-detail-modal" role="dialog" aria-modal="true">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-eye"></i> Detail Donasi</span>
            <button type="button" class="modal-close" data-donation-detail-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-error" id="donation-detail-error"></div>
            <div id="donation-detail-body"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-donation-detail-close>Tutup</button>
            <button type="button" class="btn btn-primary" id="donation-detail-edit-btn"><i class="fas fa-pen"></i> Edit</button>
            <button type="button" class="btn btn-primary" id="donation-detail-save-btn" style="display:none;"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </div>
</div>

<script>
    window.DonationDetailConfig = {!! json_encode($donationModalConfig) !!};
</script>
