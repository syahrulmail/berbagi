@extends('layouts.app')

@section('title', 'Testimoni')

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
    .testimonial-row-photo .t-file {
        font-size: 11px;
        width: 100%;
    }
    .testimonial-row-fields {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .testimonial-row-fields textarea {
        width: 100%;
    }
    .testimonial-row-fields input {
        width: 100%;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-quote-right"></i> Testimoni</h1>
        <p class="subtitle">Testimoni tokoh yang tampil di halaman utama.</p>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('settings.testimonials.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Testimoni Tokoh <small style="color: var(--gray-500); font-weight: 400;">— auto slideshow di bawah quote, tanpa judul. Foto tampil tanpa frame (unggah foto dengan latar transparan).</small></label>

            <div id="testimonials-wrap">
                @foreach($settings['home_testimonials'] as $index => $testimonial)
                <div class="testimonial-row">
                    <div class="testimonial-row-photo">
                        <img class="t-preview" src="{{ $testimonial['photo'] ? asset_photo_url($testimonial['photo']) : '' }}" alt="Foto tokoh">
                        <input type="file" class="t-file" name="testimonials[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                        <input type="hidden" class="t-existing" name="testimonials[{{ $index }}][existing_photo]" value="{{ $testimonial['photo'] }}">
                        <input type="hidden" class="t-remove-flag" name="testimonials[{{ $index }}][photo_remove]" value="0">
                        <button type="button" class="t-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Foto</button>
                    </div>
                    <div class="testimonial-row-fields">
                        <textarea class="t-text" name="testimonials[{{ $index }}][text]" rows="2" placeholder="Teks testimoni">{{ $testimonial['text'] }}</textarea>
                        <input type="text" class="t-name" name="testimonials[{{ $index }}][name]" placeholder="Nama tokoh" value="{{ $testimonial['name'] }}">
                    </div>
                    <button type="button" class="t-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>
                </div>
                @endforeach
            </div>

            <button type="button" id="add-testimonial" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Tambah Testimoni</button>
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
    var wrap = document.getElementById('testimonials-wrap');
    var addBtn = document.getElementById('add-testimonial');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.testimonial-row').length;

    function bindRow(row) {
        var file = row.querySelector('.t-file');
        var preview = row.querySelector('.t-preview');
        var removeFlag = row.querySelector('.t-remove-flag');

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

        var rmPhoto = row.querySelector('.t-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                preview.style.display = 'none';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.t-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    wrap.querySelectorAll('.testimonial-row').forEach(function (row) {
        var preview = row.querySelector('.t-preview');
        if (preview && !preview.getAttribute('src')) {
            preview.style.display = 'none';
        }
        bindRow(row);
    });

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'testimonial-row';
        row.innerHTML =
            '<div class="testimonial-row-photo">' +
                '<img class="t-preview" src="" alt="Foto tokoh" style="display:none;">' +
                '<input type="file" class="t-file" name="testimonials[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                '<input type="hidden" class="t-existing" name="testimonials[' + index + '][existing_photo]" value="">' +
                '<input type="hidden" class="t-remove-flag" name="testimonials[' + index + '][photo_remove]" value="0">' +
                '<button type="button" class="t-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Foto</button>' +
            '</div>' +
            '<div class="testimonial-row-fields">' +
                '<textarea class="t-text" name="testimonials[' + index + '][text]" rows="2" placeholder="Teks testimoni"></textarea>' +
                '<input type="text" class="t-name" name="testimonials[' + index + '][name]" placeholder="Nama tokoh">' +
            '</div>' +
            '<button type="button" class="t-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();
</script>
@endpush
