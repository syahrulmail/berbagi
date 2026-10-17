@extends('layouts.app')

@section('title', 'Mitra')

@push('styles')
<style>
    .testimonial-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fafcfc;
        margin-bottom: 10px;
    }
    .testimonial-row-photo {
        flex: 0 0 96px;
    }
    .testimonial-row-photo img {
        width: 96px;
        height: 96px;
        object-fit: contain;
        display: block;
        border: 1px dashed var(--gray-300);
        border-radius: 8px;
        background: #fff;
    }
    .testimonial-row-photo img[src=""] {
        display: none;
    }
    .testimonial-row-photo .l-file {
        font-size: 11px;
        width: 100%;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-handshake"></i> Mitra</h1>
        <p class="subtitle">Logo mitra yang tampil di halaman utama.</p>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('settings.partners.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Logo Mitra (Perusahaan/Lembaga) <small style="color: var(--gray-500); font-weight: 400;">— running nonstop di bawah testimoni, tanpa judul. Hanya gambar logo (gunakan PNG dengan latar transparan agar rapi).</small></label>

            <div id="logos-wrap">
                @foreach($settings['home_partner_logos'] as $index => $logo)
                <div class="testimonial-row">
                    <div class="testimonial-row-photo">
                        <img class="l-preview" src="{{ asset_photo_url($logo) }}" alt="Logo mitra">
                        <input type="file" class="l-file" name="logos[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                        <input type="hidden" class="l-existing" name="logos[{{ $index }}][existing_photo]" value="{{ $logo }}">
                        <input type="hidden" class="l-remove-flag" name="logos[{{ $index }}][photo_remove]" value="0">
                        <button type="button" class="l-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Logo</button>
                    </div>
                    <button type="button" class="l-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>
                </div>
                @endforeach
            </div>

            <button type="button" id="add-logo" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Tambah Logo</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('logos-wrap');
    var addBtn = document.getElementById('add-logo');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.testimonial-row').length;

    function bindLogoRow(row) {
        var file = row.querySelector('.l-file');
        var preview = row.querySelector('.l-preview');
        var removeFlag = row.querySelector('.l-remove-flag');

        if (file && preview) {
            file.addEventListener('change', function () {
                var f = file.files && file.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(f);
            });
        }

        var rmPhoto = row.querySelector('.l-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                preview.style.display = 'none';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.l-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    wrap.querySelectorAll('.testimonial-row').forEach(function (row) {
        var preview = row.querySelector('.l-preview');
        if (preview && !preview.getAttribute('src')) {
            preview.style.display = 'none';
        }
        bindLogoRow(row);
    });

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'testimonial-row';
        row.innerHTML =
            '<div class="testimonial-row-photo">' +
                '<img class="l-preview" src="" alt="Logo mitra" style="display:none;">' +
                '<input type="file" class="l-file" name="logos[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                '<input type="hidden" class="l-existing" name="logos[' + index + '][existing_photo]" value="">' +
                '<input type="hidden" class="l-remove-flag" name="logos[' + index + '][photo_remove]" value="0">' +
                '<button type="button" class="l-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Logo</button>' +
            '</div>' +
            '<button type="button" class="l-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>';
        wrap.appendChild(row);
        bindLogoRow(row);
        index++;
    });
})();
</script>
@endpush
