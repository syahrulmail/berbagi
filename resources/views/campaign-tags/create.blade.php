@extends('layouts.app')

@section('title', 'Tambah Label Kampanye')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-tags"></i> Tambah Label Kampanye</h1>
        <p class="subtitle">Buat satu atau beberapa label sekaligus, dipisahkan dengan koma (,).</p>
    </div>
    <a href="{{ route('campaign-tags.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 560px;">
    <form method="POST" action="{{ route('campaign-tags.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Nama Label (pisahkan dengan koma) *</label>
            <textarea id="name" name="name" rows="3" placeholder="Mendesak, Donasi Hewan Qurban, Peduli Palestina" required>{{ old('name') }}</textarea>
            <small style="color: var(--gray-500);">Pisahkan setiap label dengan koma (<code>,</code>). Label yang sudah ada akan dilewati otomatis.</small>
        </div>
        <div class="form-group">
            <label for="color">Warna *</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="color" id="color" name="color" value="{{ old('color', '#08A899') }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
                <span id="colorName" style="font-size: 13px; color: var(--gray-500);">Pilih warna untuk semua label baru</span>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('campaign-tags.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('name');
    var color = document.getElementById('color');
    if (!input || !color) return;

    var colorName = document.getElementById('colorName');

    function parseList(str) {
        var seen = {};
        var out = [];
        str.split(',').forEach(function (p) {
            var t = p.trim();
            if (!t) return;
            var key = t.toLowerCase();
            if (seen[key]) return;
            seen[key] = true;
            out.push(t);
        });
        return out.slice(0, 50);
    }

    var hint = document.createElement('small');
    hint.style.color = 'var(--primary)';
    hint.style.fontWeight = '600';
    input.parentNode.appendChild(hint);

    function update() {
        var n = parseList(input.value).length;
        hint.textContent = n === 0
            ? ''
            : (n === 1 ? '1 label akan dibuat.' : n + ' label akan dibuat.');
    }

    color.addEventListener('input', function () {
        colorName.textContent = 'Warna terpilih: ' + color.value;
    });
    input.addEventListener('input', update);
    update();
})();
</script>
@endpush
@endsection
