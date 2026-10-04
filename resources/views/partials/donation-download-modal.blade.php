{{--
    Modal Download Donasi untuk halaman Manajemen Donasi.
    Mengirim filter (multi cabang + periode) ke route donations.download.
--}}
@push('styles')
<style>
    #donation-download-modal .checkbox-group {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 14px;
        margin-top: 8px;
        max-height: 190px;
        overflow-y: auto;
        padding: 10px 14px;
        border: 1px solid #d2e2e0;
        border-radius: 10px;
        background: #fbfdfd;
    }

    #donation-download-modal .checkbox-group .checkbox-label {
        margin: 0;
    }

    .download-hint {
        margin-top: 4px;
        color: #5e7472;
        font-size: 12.5px;
    }

    @media (max-width: 640px) {
        #donation-download-modal .checkbox-group {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

<div class="modal-backdrop" id="donation-download-modal" role="dialog" aria-modal="true">
    <div class="modal">
        <form method="GET" action="{{ route('donations.download') }}" id="donation-download-form">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-download"></i> Download Donasi</span>
                <button type="button" class="modal-close" data-donation-download-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Cabang</label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="donation-download-all-branches">
                        <span>Pilih semua cabang</span>
                    </label>
                    <div class="checkbox-group" id="donation-download-branches">
                        @forelse($downloadBranches as $branch)
                            <label class="checkbox-label">
                                <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}">
                                <span>{{ $branch->name }}</span>
                            </label>
                        @empty
                            <span class="muted">Belum ada cabang aktif.</span>
                        @endforelse
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="donation-download-from">Dari Tanggal</label>
                        <input type="date" id="donation-download-from" name="from">
                    </div>
                    <div class="form-group">
                        <label for="donation-download-to">Sampai Tanggal</label>
                        <input type="date" id="donation-download-to" name="to">
                    </div>
                </div>
                <p class="download-hint">
                    @if(auth()->user() && auth()->user()->isAgen())
                        Anda hanya dapat memilih cabang Anda dan hanya donasi milik Anda yang akan diunduh.
                    @elseif(auth()->user() && auth()->user()->isSupervisor())
                        Anda hanya dapat memilih cabang Anda sendiri.
                    @else
                        Kosongkan cabang atau tanggal untuk mengunduh seluruh data sesuai akses Anda.
                    @endif
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-donation-download-close>Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-excel"></i> Download XLSX</button>
                <button type="submit" class="btn btn-outline" formaction="{{ route('donations.download-proof') }}"><i class="fas fa-file-word"></i> Download BT</button>
            </div>
        </form>
    </div>
</div>
