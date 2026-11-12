@extends('mobile.layouts.app')

@section('title', 'Postingan Instagram')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fab fa-instagram" style="color:var(--mo-primary);font-size:19px;"></i> Postingan Instagram</h1>
            <div class="mo-appbar-sub">Tampil di beranda &amp; halaman agen</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.instagram.update') }}" class="mo-form">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-link"></i> URL Postingan</h3>

            <div class="mo-form-help" style="margin-bottom:10px;">
                Tempel tautan postingan, reel, atau IGTV. Tampil sebagai embed resmi Instagram di bawah kartu pencapaian.
            </div>

            <div id="mo-ig-wrap">
                @foreach($settings['home_instagram_posts'] as $index => $post)
                    <div class="mo-ig-row" style="border:1px solid var(--mo-border);border-radius:12px;padding:10px;margin-bottom:10px;background:#fafcfc;">
                        <div class="mo-field" style="margin-bottom:6px;">
                            <input type="url" class="mo-input" name="posts[{{ $index }}][url]" value="{{ $post['url'] }}" placeholder="https://www.instagram.com/p/DfmbqAppDMi/" maxlength="255">
                        </div>
                        <div class="mo-form-help" style="word-break:break-all;{{ $post['embed'] === '' ? 'color:var(--mo-danger);' : '' }}">
                            @if($post['embed'] === '')
                                Tautan tidak dikenali sebagai postingan Instagram.
                            @else
                                Embed: {{ $post['embed'] }}<br>
                                Rasio media: {{ $post['ratio'] ? number_format($post['ratio'], 3, ',', '.') : 'belum terdeteksi' }}<br>
                                Sampul ringan: {{ $post['thumbnail'] ? 'tersedia' : 'belum terdeteksi' }}
                            @endif
                        </div>
                        <button type="button" class="mo-ig-rm mo-remove-btn" style="margin-top:8px;width:100%;"><i class="fas fa-trash-can"></i> Hapus</button>
                    </div>
                @endforeach
            </div>

            <button type="button" id="mo-add-ig" class="mo-btn mo-btn-ghost" style="width:100%;"><i class="fas fa-plus"></i> Tambah Postingan</button>

            <div class="mo-form-help" style="margin-top:10px;">
                Contoh: <code>https://www.instagram.com/p/KODE/</code> atau
                <code>https://www.instagram.com/reel/KODE/</code>. Akun harus publik.
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .mo-ig-rm {
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
        display: flex;
        align-items: center;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('mo-ig-wrap');
    var addBtn = document.getElementById('mo-add-ig');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.mo-ig-row').length;

    function bindRow(row) {
        var rm = row.querySelector('.mo-ig-rm');
        if (rm) {
            rm.addEventListener('click', function () { row.remove(); });
        }
    }

    wrap.querySelectorAll('.mo-ig-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'mo-ig-row';
        row.style.cssText = 'border:1px solid var(--mo-border);border-radius:12px;padding:10px;margin-bottom:10px;background:#fafcfc;';
        row.innerHTML =
            '<div class="mo-field" style="margin-bottom:6px;">' +
                '<input type="url" class="mo-input" name="posts[' + index + '][url]" value="" placeholder="https://www.instagram.com/p/DfmbqAppDMi/" maxlength="255">' +
            '</div>' +
            '<div class="mo-form-help">Embed dibuat otomatis saat disimpan.</div>' +
            '<button type="button" class="mo-ig-rm mo-remove-btn" style="margin-top:8px;width:100%;"><i class="fas fa-trash-can"></i> Hapus</button>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();
</script>
@endpush
