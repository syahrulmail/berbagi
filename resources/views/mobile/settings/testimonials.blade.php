@extends('mobile.layouts.app')

@section('title', 'Testimoni')

@push('styles')
<style>
    .mo-testi-row {
        border: 1px solid var(--mo-border);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fafcfc;
    }
    .mo-testi-top {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    .mo-testi-photo {
        width: 72px;
        height: 72px;
        flex: 0 0 72px;
        border-radius: 10px;
        border: 1px dashed var(--mo-border);
        background: #fff center/contain no-repeat;
        overflow: hidden;
    }
    .mo-testi-photo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .mo-testi-photo img[src=""] { display: none; }
    .mo-testi-actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }
    .mo-testi-actions label {
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
    .mo-testi-actions input[type=file] { display: none; }
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
            <h1 class="mo-appbar-title"><i class="fas fa-quote-right" style="color:var(--mo-primary);font-size:19px;"></i> Testimoni</h1>
            <div class="mo-appbar-sub">Testimoni tokoh di halaman utama</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.testimonials.update') }}" enctype="multipart/form-data" class="mo-form">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-users"></i> Daftar Testimoni</h3>

            <div id="mo-testi-wrap">
                @foreach($settings['home_testimonials'] as $index => $testimonial)
                <div class="mo-testi-row">
                    <div class="mo-testi-top">
                        <div class="mo-testi-photo">
                            <img class="t-preview" src="{{ $testimonial['photo'] ? asset_photo_url($testimonial['photo']) : '' }}" alt="Foto tokoh">
                        </div>
                        <div class="mo-testi-actions">
                            <label>
                                <i class="fas fa-camera"></i> Pilih Foto
                                <input type="file" class="t-file" name="testimonials[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                            </label>
                            <input type="hidden" class="t-existing" name="testimonials[{{ $index }}][existing_photo]" value="{{ $testimonial['photo'] }}">
                            <input type="hidden" class="t-remove-flag" name="testimonials[{{ $index }}][photo_remove]" value="0">
                            <button type="button" class="t-rm-photo mo-remove-btn"><i class="fas fa-trash-can"></i> Hapus Foto</button>
                            <button type="button" class="t-rm-row mo-remove-btn"><i class="fas fa-xmark"></i> Hapus Testimoni</button>
                        </div>
                    </div>
                    <div class="mo-field" style="margin-bottom:8px;">
                        <textarea class="t-text mo-textarea" name="testimonials[{{ $index }}][text]" rows="2" maxlength="500" placeholder="Teks testimoni">{{ $testimonial['text'] }}</textarea>
                    </div>
                    <div class="mo-field" style="margin-bottom:0;">
                        <input type="text" class="t-name mo-input" name="testimonials[{{ $index }}][name]" maxlength="100" placeholder="Nama tokoh" value="{{ $testimonial['name'] }}">
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" id="mo-add-testimonial" class="mo-btn mo-btn-ghost" style="width:100%;"><i class="fas fa-plus"></i> Tambah Testimoni</button>
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
    var wrap = document.getElementById('mo-testi-wrap');
    var addBtn = document.getElementById('mo-add-testimonial');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.mo-testi-row').length;

    function bindRow(row) {
        var file = row.querySelector('.t-file');
        var preview = row.querySelector('.t-preview');
        var removeFlag = row.querySelector('.t-remove-flag');

        if (file && preview) {
            file.addEventListener('change', function () {
                var f = file.files && file.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; };
                reader.readAsDataURL(f);
            });
        }

        var rmPhoto = row.querySelector('.t-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.t-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () { row.remove(); });
        }
    }

    wrap.querySelectorAll('.mo-testi-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'mo-testi-row';
        row.innerHTML =
            '<div class="mo-testi-top">' +
                '<div class="mo-testi-photo"><img class="t-preview" src="" alt="Foto tokoh"></div>' +
                '<div class="mo-testi-actions">' +
                    '<label><i class="fas fa-camera"></i> Pilih Foto' +
                        '<input type="file" class="t-file" name="testimonials[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                    '</label>' +
                    '<input type="hidden" class="t-existing" name="testimonials[' + index + '][existing_photo]" value="">' +
                    '<input type="hidden" class="t-remove-flag" name="testimonials[' + index + '][photo_remove]" value="0">' +
                    '<button type="button" class="t-rm-photo mo-remove-btn"><i class="fas fa-trash-can"></i> Hapus Foto</button>' +
                    '<button type="button" class="t-rm-row mo-remove-btn"><i class="fas fa-xmark"></i> Hapus Testimoni</button>' +
                '</div>' +
            '</div>' +
            '<div class="mo-field" style="margin-bottom:8px;">' +
                '<textarea class="t-text mo-textarea" name="testimonials[' + index + '][text]" rows="2" maxlength="500" placeholder="Teks testimoni"></textarea>' +
            '</div>' +
            '<div class="mo-field" style="margin-bottom:0;">' +
                '<input type="text" class="t-name mo-input" name="testimonials[' + index + '][name]" maxlength="100" placeholder="Nama tokoh">' +
            '</div>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();
</script>
@endpush
