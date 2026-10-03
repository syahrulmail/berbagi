{{--
    Modal daftar donatur sebuah program untuk halaman Manajemen Program.
    Memuat data via AJAX (programs.donors).
--}}
@php
    $programDonorsConfig = [
        'url' => route('programs.donors', ['program' => '__ID__']),
    ];
@endphp
<div class="modal-backdrop" id="program-donors-modal" role="dialog" aria-modal="true">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-hand-holding-heart"></i> Donatur: <span id="program-donors-name"></span></span>
            <button type="button" class="modal-close" data-program-donors-close>&times;</button>
        </div>
        <div class="modal-body">
            <div id="program-donors-body"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-program-donors-close>Tutup</button>
        </div>
    </div>
</div>

<script>
    window.ProgramDonorsConfig = {!! json_encode($programDonorsConfig) !!};
</script>
