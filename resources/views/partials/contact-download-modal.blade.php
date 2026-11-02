{{--
    Modal Download Kontak untuk halaman Manajemen Kontak.
    Memilih cabang/agen lalu menghitung jumlah kontak sebelum mengunduh XLSX.
--}}
@php
    $downloadUser = auth()->user();
    $downloadIsSupervisor = $downloadUser->isSupervisor();
    $downloadBranchId = $downloadIsSupervisor
        ? $downloadUser->branch_id
        : request('branch_id');
    $downloadAgenId = request('agen_id');
@endphp
@push('styles')
<style>
    .contact-download-count {
        margin-top: 12px;
        padding: 10px 14px;
        border-radius: 10px;
        background: #f0f7f6;
        color: #00544c;
        font-size: 13px;
        font-weight: 600;
    }
    #contact-download-modal .download-hint {
        margin: 6px 0 0;
        color: #5e7472;
        font-size: 12px;
    }
</style>
@endpush
<div class="modal-backdrop" id="contact-download-modal" role="dialog" aria-modal="true">
    <div class="modal">
        <form method="GET" action="{{ route('contacts.download') }}" id="contact-download-form"
              data-contact-download-form
              data-count-url="{{ route('contacts.download.count') }}">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-download"></i> Download Kontak</span>
                <button type="button" class="modal-close" data-contact-download-close>&times;</button>
            </div>
            <div class="modal-body">
                @foreach(['search', 'status', 'from', 'to'] as $preserved)
                    @if(request()->filled($preserved))
                        <input type="hidden" name="{{ $preserved }}" value="{{ request($preserved) }}">
                    @endif
                @endforeach

                <div class="form-group">
                    <label for="contact-download-branch">Cabang</label>
                    @if($downloadIsSupervisor)
                        <input type="hidden" name="branch_id" value="{{ $downloadBranchId }}">
                        <select id="contact-download-branch" data-download-branch disabled>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" selected>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <p class="download-hint">Cabang otomatis mengikuti cabang yang Anda kelola.</p>
                    @else
                        <select id="contact-download-branch" name="branch_id" data-download-branch>
                            <option value="">— Semua Cabang —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $downloadBranchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="form-group">
                    <label for="contact-download-agen">Agen</label>
                    <select id="contact-download-agen" name="agen_id" data-download-agen>
                        <option value="">— Semua Agen —</option>
                        @foreach($agents as $agen)
                            <option value="{{ $agen->id }}" data-branch="{{ $agen->branch_id }}" {{ (string) $downloadAgenId === (string) $agen->id ? 'selected' : '' }}>{{ $agen->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="contact-download-count" data-download-count>Menghitung kontak...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-contact-download-close>Batal</button>
                <button type="submit" class="btn btn-primary" data-download-submit>
                    <i class="fas fa-file-excel"></i> Download Kontak
                </button>
            </div>
        </form>
    </div>
</div>
