{{--
    Modal Rincian Kontak untuk halaman Manajemen Kontak.
    Memuat data rincian via AJAX (contacts.detail) dan form edit via AJAX (contacts.edit-fields).
--}}
@php
    $contactModalConfig = [
        'detailUrl' => route('contacts.detail', ['contact' => '__ID__']),
        'editFieldsUrl' => route('contacts.edit-fields', ['contact' => '__ID__']),
        'updateUrl' => route('contacts.update', ['contact' => '__ID__']),
    ];
@endphp
<div class="modal-backdrop" id="contact-detail-modal" role="dialog" aria-modal="true">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-eye"></i> Detail Kontak</span>
            <button type="button" class="modal-close" data-contact-detail-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-error" id="contact-detail-error"></div>
            <div id="contact-detail-body"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-contact-detail-close>Tutup</button>
            <button type="button" class="btn btn-primary" id="contact-detail-edit-btn"><i class="fas fa-pen"></i> Edit</button>
            <button type="button" class="btn btn-primary" id="contact-detail-save-btn" style="display:none;"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </div>
</div>

<script>
    window.ContactDetailConfig = {!! json_encode($contactModalConfig) !!};
</script>
