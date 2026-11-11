@extends('layouts.app')

@section('title', 'Postingan Instagram')

@push('styles')
<style>
    .ig-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fafcfc;
        margin-bottom: 10px;
    }
    .ig-row-preview {
        flex: 0 0 120px;
        width: 120px;
        height: 120px;
        border-radius: 10px;
        border: 1px solid var(--gray-200);
        background: #fff center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-400);
        font-size: 11px;
        text-align: center;
        padding: 6px;
        overflow: hidden;
    }
    .ig-row-fields {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .ig-row-fields input {
        width: 100%;
    }
    .ig-row-hint {
        font-size: 11.5px;
        color: var(--gray-500);
        word-break: break-all;
    }
    .ig-row-hint.error {
        color: #b91c1c;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fab fa-instagram"></i> Postingan Instagram</h1>
        <p class="subtitle">Postingan Instagram yang tampil di bawah kartu pencapaian pada beranda dan halaman agen.</p>
    </div>
</div>

<div class="card" style="max-width: 760px;">
    <form method="POST" action="{{ route('settings.instagram.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>URL Postingan <small style="color: var(--gray-500); font-weight: 400;">— tempel tautan postingan, reel, atau IGTV. Tampil sebagai embed resmi Instagram (video diputar di tempat, tanpa pindah ke Instagram).</small></label>

            <div id="ig-wrap">
                @foreach($settings['home_instagram_posts'] as $index => $post)
                    <div class="ig-row">
                        <div class="ig-row-preview">Embed<br>Instagram</div>
                        <div class="ig-row-fields">
                            <input type="url" name="posts[{{ $index }}][url]" value="{{ $post['url'] }}" placeholder="https://www.instagram.com/p/DfmbqAppDMi/" maxlength="255">
                            <div class="ig-row-hint {{ $post['embed'] === '' ? 'error' : '' }}">
                                @if($post['embed'] === '')
                                    Tautan tidak dikenali sebagai postingan Instagram.
                                @else
                                    Embed: {{ $post['embed'] }}<br>
                                    Rasio media: {{ $post['ratio'] ? number_format($post['ratio'], 3, ',', '.') : 'belum terdeteksi (kartu memakai bentuk persegi)' }}
                                @endif
                            </div>
                        </div>
                        <button type="button" class="ig-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-ig" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Tambah Postingan</button>

            <small style="display:block; color: var(--gray-500); margin-top: 10px;">
                Contoh format yang diterima:
                <code>https://www.instagram.com/p/KODE/</code>,
                <code>https://www.instagram.com/reel/KODE/</code>,
                <code>https://www.instagram.com/tv/KODE/</code>.
                Postingan harus berasal dari akun publik. Bentuk kartu mengikuti
                rasio media yang dibaca saat URL disimpan.
            </small>
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
    var wrap = document.getElementById('ig-wrap');
    var addBtn = document.getElementById('add-ig');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.ig-row').length;

    function bindRow(row) {
        var rm = row.querySelector('.ig-rm-row');
        if (rm) {
            rm.addEventListener('click', function () { row.remove(); });
        }
    }

    wrap.querySelectorAll('.ig-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'ig-row';
        row.innerHTML =
            '<div class="ig-row-preview">Embed<br>Instagram</div>' +
            '<div class="ig-row-fields">' +
                '<input type="url" name="posts[' + index + '][url]" value="" placeholder="https://www.instagram.com/p/DfmbqAppDMi/" maxlength="255">' +
                '<div class="ig-row-hint">Embed akan dibuat otomatis saat disimpan.</div>' +
            '</div>' +
            '<button type="button" class="ig-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();
</script>
@endpush
