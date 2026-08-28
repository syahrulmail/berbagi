@push('styles')
<style>
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

<div class="form-group">
    <label for="tag_names">Campaign Tags</label>
    <input type="text" id="tag_names" name="tag_names" value="{{ $tagNamesValue }}" placeholder="Mendesak, Donasi Hewan Qurban" autocomplete="off">
    <small style="color: var(--gray-500);">Pisahkan dengan koma (<code>,</code>). Tag baru akan dibuat otomatis. Klik label di bawah untuk menambah.</small>
    @if(count($tags) > 0)
    <div class="tag-suggest">
        @foreach($tags as $tag)
        <button type="button" class="tag-pill tag-suggest-btn" style="background: {{ $tag->color }}" data-tag="{{ $tag->name }}">{{ $tag->name }}</button>
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
