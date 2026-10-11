<div class="mo-sheet-backdrop" data-for="mo-donation-sheet"></div>
<div class="mo-sheet" id="mo-donation-sheet" aria-hidden="true">
    <div class="mo-sheet-handle"></div>
    <div class="mo-sheet-head">
        <h3 class="mo-sheet-title"><i class="fas fa-hand-holding-dollar" style="color:var(--mo-primary);margin-right:6px;"></i>Detail Donasi</h3>
        <button type="button" class="mo-sheet-close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="mo-sheet-body" id="mo-donation-sheet-body">
        <div class="mo-empty"><p>Memuat...</p></div>
    </div>
</div>

<div class="mo-sheet-backdrop" data-for="mo-donation-delete-confirm"></div>
<div class="mo-sheet" id="mo-donation-delete-confirm" aria-hidden="true">
    <div class="mo-sheet-handle"></div>
    <div class="mo-sheet-head">
        <h3 class="mo-sheet-title"><i class="fas fa-triangle-exclamation" style="color:var(--mo-danger);margin-right:6px;"></i>Hapus Donasi</h3>
        <button type="button" class="mo-sheet-close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="mo-sheet-body">
        <p style="font-size:14px;color:var(--mo-text);margin:0 0 16px;">Apakah yakin menghapus data ini?</p>
        <div style="display:flex;gap:10px;">
            <button type="button" class="mo-btn mo-btn-ghost" style="flex:1;" data-sheet-cancel="mo-donation-delete-confirm">Batal</button>
            <button type="button" class="mo-btn mo-btn-danger" style="flex:1;" id="mo-donation-delete-confirm-btn"><i class="fas fa-trash-can"></i> Ya Hapus Donasi</button>
        </div>
    </div>
</div>
