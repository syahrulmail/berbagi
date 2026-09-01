@php
    $mediaItems = $mediaItems ?? [];
    $videoUrl = $videoUrl ?? '';
@endphp

@push('styles')
<style>
    .program-media-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 10px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        margin-bottom: 8px;
        background: #fafcfc;
    }
    .program-media-thumb {
        width: 64px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--gray-200);
        background: #fff;
        flex-shrink: 0;
    }
    .program-media-fields {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .program-media-fields label {
        font-size: 12px;
        color: var(--gray-600);
    }
    .media-order-input {
        width: 70px;
        padding: 6px 8px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 13px;
    }
    .media-order-note {
        font-size: 11px;
        color: var(--gray-500);
    }
    .media-replace-input {
        font-size: 11px;
        max-width: 180px;
    }
    .media-remove-new {
        border: none;
        background: none;
        color: var(--danger, #e74c3c);
        font-size: 15px;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 6px;
        line-height: 1;
    }
    .media-remove-new:hover {
        background: #fdecea;
    }
</style>
@endpush

<div class="form-group">
    <label>Media &amp; Gambar</label>

    <div class="form-group" style="margin-bottom:10px;">
        <label for="video_url">Link Video YouTube</label>
        <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $videoUrl) }}"
               placeholder="https://www.youtube.com/watch?v=...">
        <small style="color: var(--gray-500); display:block; margin-top:4px;">Salin &amp; tempel link video dari YouTube (contoh: https://www.youtube.com/watch?v=VIDEO_ID). Video tampil sebagai embed di halaman publik.</small>
    </div>

    <div class="form-group" style="margin-bottom:10px;">
        <label>Gambar Program</label>
        <div id="programMediaList">
            @foreach($mediaItems as $i => $item)
            <div class="program-media-row">
                <img class="program-media-thumb" src="{{ $item['url'] }}" alt="Gambar program {{ $item['order'] }}">
                <div class="program-media-fields">
                    <label for="media_order_{{ $i }}">Prioritas</label>
                    <input type="number" id="media_order_{{ $i }}" name="media_orders[{{ $i }}]"
                           value="{{ old('media_orders.' . $i, $item['order']) }}" min="0" max="999" class="media-order-input">
                    <label for="media_replace_{{ $i }}">Ganti</label>
                    <input type="file" id="media_replace_{{ $i }}" name="media_replace[{{ $i }}]"
                           accept="image/jpeg,image/png,image/webp" class="media-replace-input">
                    <label class="inline-check" style="margin:0;">
                        <input type="checkbox" name="media_remove[{{ $i }}]" value="1"> Hapus
                    </label>
                </div>
                <input type="hidden" name="media_paths[{{ $i }}]" value="{{ $item['path'] }}">
            </div>
            @endforeach
        </div>
        <div id="programMediaNew"></div>
        <input type="file" id="programMediaFiles" name="media_files[]" accept="image/jpeg,image/png,image/webp" multiple class="t-file">
        <small style="color: var(--gray-500); display:block; margin-top:6px;">JPG/PNG/WebP, maks. 4MB per gambar. Disarankan rasio 4:3 dengan ukuran minimal <strong>1200x900 px</strong> (idealnya <strong>1280x960 px</strong>). Gambar dengan prioritas terkecil (misal 0) menjadi sampul program dan tampil pertama di galeri.</small>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';
    var input = document.getElementById('programMediaFiles');
    var container = document.getElementById('programMediaNew');
    if (!input || !container) return;

    var pending = [];

    function existingOrderInputs() {
        var orders = Array.prototype.map.call(document.querySelectorAll('#programMediaList .media-order-input'), function (el) {
            return parseInt(el.value, 10);
        }).filter(function (n) { return !isNaN(n); });
        return orders.length ? Math.max.apply(null, orders) : -1;
    }

    function renderPending() {
        container.innerHTML = '';
        var base = existingOrderInputs() + 1;
        pending.forEach(function (file, i) {
            var row = document.createElement('div');
            row.className = 'program-media-row program-media-new';
            var img = document.createElement('img');
            img.className = 'program-media-thumb';
            if (file.type.indexOf('image/') === 0 && window.URL && URL.createObjectURL) {
                img.src = URL.createObjectURL(file);
            }
            img.alt = file.name;
            var fields = document.createElement('div');
            fields.className = 'program-media-fields';
            var lbl = document.createElement('label');
            lbl.textContent = 'Prioritas';
            var ord = document.createElement('input');
            ord.type = 'number';
            ord.name = 'media_new_orders[]';
            ord.value = String(base + i);
            ord.min = '0';
            ord.max = '999';
            ord.className = 'media-order-input';
            fields.appendChild(lbl);
            fields.appendChild(ord);
            var name = document.createElement('span');
            name.className = 'media-order-note';
            name.textContent = file.name;
            fields.appendChild(name);
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'media-remove-new';
            rm.title = 'Batalkan gambar ini';
            rm.innerHTML = '<i class="fas fa-xmark"></i>';
            rm.addEventListener('click', function () {
                pending.splice(i, 1);
                renderPending();
            });
            fields.appendChild(rm);
            row.appendChild(img);
            row.appendChild(fields);
            container.appendChild(row);
        });

        if (window.DataTransfer) {
            var dt = new DataTransfer();
            pending.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }
    }

    input.addEventListener('change', function () {
        pending = pending.concat(Array.prototype.slice.call(input.files || []));
        renderPending();
    });
})();
</script>
@endpush
