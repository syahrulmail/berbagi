@extends('mobile.layouts.app')

@section('title', 'Mitra')

@push('styles')
<style>
    .mo-logo-row {
        border: 1px solid var(--mo-border);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fafcfc;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .mo-logo-photo {
        width: 84px;
        height: 84px;
        flex: 0 0 84px;
        border-radius: 10px;
        border: 1px dashed var(--mo-border);
        background: #fff center/contain no-repeat;
        overflow: hidden;
    }
    .mo-logo-photo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .mo-logo-photo img[src=""] { display: none; }
    .mo-logo-actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }
    .mo-logo-actions label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 10px;
        background: #eef5f4;
        color: var(--mo-primary);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .mo-logo-actions input[type=file] { display: none; }
    .mo-remove-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 10px;
        background: #fdeeec;
        color: var(--mo-danger);
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
</style>
@endpush

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-handshake" style="color:var(--mo-primary);font-size:19px;"></i> Mitra</h1>
            <div class="mo-appbar-sub">Logo mitra di halaman utama</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.partners.update') }}" enctype="multipart/form-data" class="mo-form">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-images"></i> Logo Mitra</h3>
            <div class="mo-form-help" style="margin-bottom:10px;">Gunakan PNG latar transparan agar tampilan rapi.</div>

            <div id="mo-logos-wrap">
                @foreach($settings['home_partner_logos'] as $index => $logo)
                <div class="mo-logo-row">
                    <div class="mo-logo-photo">
                        <img class="l-preview" src="{{ asset_photo_url($logo) }}" alt="Logo mitra">
                    </div>
                    <div class="mo-logo-actions">
                        <label>
                            <i class="fas fa-image"></i> Pilih Logo
                            <input type="file" class="l-file" name="logos[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                        </label>
                        <input type="hidden" class="l-existing" name="logos[{{ $index }}][existing_photo]" value="{{ $logo }}">
                        <input type="hidden" class="l-remove-flag" name="logos[{{ $index }}][photo_remove]" value="0">
                        <button type="button" class="l-rm-photo mo-remove-btn"><i class="fas fa-trash-can"></i> Hapus Logo</button>
                        <button type="button" class="l-rm-row mo-remove-btn"><i class="fas fa-xmark"></i> Hapus Baris</button>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" id="mo-add-logo" class="mo-btn mo-btn-ghost" style="width:100%;"><i class="fas fa-plus"></i> Tambah Logo</button>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('mo-logos-wrap');
    var addBtn = document.getElementById('mo-add-logo');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.mo-logo-row').length;

    function bindRow(row) {
        var file = row.querySelector('.l-file');
        var preview = row.querySelector('.l-preview');
        var removeFlag = row.querySelector('.l-remove-flag');

        if (file && preview) {
            file.addEventListener('change', function () {
                var f = file.files && file.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; };
                reader.readAsDataURL(f);
            });
        }

        var rmPhoto = row.querySelector('.l-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.l-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () { row.remove(); });
        }
    }

    wrap.querySelectorAll('.mo-logo-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'mo-logo-row';
        row.innerHTML =
            '<div class="mo-logo-photo"><img class="l-preview" src="" alt="Logo mitra"></div>' +
            '<div class="mo-logo-actions">' +
                '<label><i class="fas fa-image"></i> Pilih Logo' +
                    '<input type="file" class="l-file" name="logos[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                '</label>' +
                '<input type="hidden" class="l-existing" name="logos[' + index + '][existing_photo]" value="">' +
                '<input type="hidden" class="l-remove-flag" name="logos[' + index + '][photo_remove]" value="0">' +
                '<button type="button" class="l-rm-photo mo-remove-btn"><i class="fas fa-trash-can"></i> Hapus Logo</button>' +
                '<button type="button" class="l-rm-row mo-remove-btn"><i class="fas fa-xmark"></i> Hapus Baris</button>' +
            '</div>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();
</script>
@endpush
