@push('styles')
<style>
    .default-tag-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .default-tag-option {
        position: relative;
        cursor: pointer;
    }
    .default-tag-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .default-tag-option .tag-pill {
        display: inline-block;
        padding: 7px 16px;
        border-radius: 9999px;
        font-size: 12.5px;
        font-weight: 700;
        border: 2px solid transparent;
        transition: all .15s ease;
        margin-right: 0;
    }
    .default-tag-option input:checked + .tag-pill {
        border-color: #043d3a;
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #043d3a;
        transform: scale(1.03);
    }
    .default-tag-option input:focus-visible + .tag-pill {
        box-shadow: 0 0 0 3px rgba(8, 168, 153, .4);
    }
    .default-tag-options.is-invalid .tag-pill {
        border-color: #e74c3c;
    }
    .tag-suggest {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }
    .tag-suggest-btn {
        cursor: pointer;
        border: none;
        transition: opacity .15s, transform .15s;
    }
    .tag-suggest-btn:hover {
        opacity: .85;
        transform: scale(1.05);
    }
</style>
@endpush

@php
    $tagTextColor = function ($hex) {
        $h = ltrim((string) $hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        $n = hexdec($h);
        $r = ($n >> 16) & 255;
        $g = ($n >> 8) & 255;
        $b = $n & 255;
        return ((0.299 * $r + 0.587 * $g + 0.114 * $b) / 255) > 0.6 ? '#0b2f2d' : '#ffffff';
    };
@endphp

<div class="form-group">
    <label>Tag Prioritas <span class="text-danger">*</span></label>
    <div class="default-tag-options {{ $errors->has('default_tag') ? 'is-invalid' : '' }}">
        @foreach($defaultTags as $tag)
        <label class="default-tag-option">
            <input type="radio" name="default_tag" value="{{ $tag->slug }}" {{ $defaultTagValue === $tag->slug ? 'checked' : '' }} required>
            <span class="tag-pill" style="background: {{ $tag->color }}; color: {{ $tagTextColor($tag->color) }};">{{ $tag->name }}</span>
        </label>
        @endforeach
    </div>
    @error('default_tag')
        <small style="display:block; color: var(--danger);">{{ $message }}</small>
    @enderror
    <small style="color: var(--gray-500);">Wajib memilih salah satu: Prioritas, Mendesak, Istimewa, atau Diminati.</small>
</div>

<div class="form-group">
    <label for="tag_names">Tag Tambahan <small style="color: var(--gray-500); font-weight:400;">(opsional)</small></label>
    <input type="text" id="tag_names" name="tag_names" value="{{ $tagNamesValue }}" placeholder="Donasi Hewan Qurban, Peduli Palestina" autocomplete="off">
    <small style="color: var(--gray-500);">Pisahkan dengan koma (<code>,</code>). Tag baru akan dibuat otomatis. Klik label di bawah untuk menambah.</small>
    @if(count($extraTags) > 0)
    <div class="tag-suggest">
        @foreach($extraTags as $tag)
        <button type="button" class="tag-pill tag-suggest-btn" style="background: {{ $tag->color }}; color: {{ $tagTextColor($tag->color) }};" data-tag="{{ $tag->name }}">{{ $tag->name }}</button>
        @endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('tag_names');
    if (!input) return;

    var btns = document.querySelectorAll('.tag-suggest-btn');

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-tag');
            var current = input.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var idx = current.findIndex(function (t) { return t.toLowerCase() === name.toLowerCase(); });

            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(name);
            }

            input.value = current.join(', ');
        });
    });
})();
</script>
@endpush
