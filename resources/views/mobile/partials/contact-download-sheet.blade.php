@php
    $downloadUser = auth()->user();
    $downloadIsSupervisor = $downloadUser->isSupervisor();
    $downloadBranchId = $downloadIsSupervisor
        ? $downloadUser->branch_id
        : request('branch_id');
    $downloadAgenId = request('agen_id');
@endphp
<div class="mo-sheet-backdrop" data-for="mo-contact-download-sheet"></div>
<div class="mo-sheet" id="mo-contact-download-sheet" aria-hidden="true">
    <div class="mo-sheet-handle"></div>
    <div class="mo-sheet-head">
        <h3 class="mo-sheet-title"><i class="fas fa-download" style="color:var(--mo-primary);margin-right:6px;"></i>Download Kontak</h3>
        <button type="button" class="mo-sheet-close" data-contact-download-close aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="mo-sheet-body">
        <form method="GET" action="{{ route('contacts.download') }}" id="mo-contact-download-form"
              data-contact-download-form
              data-count-url="{{ route('contacts.download.count') }}">
            @foreach(['search', 'status', 'from', 'to'] as $preserved)
                @if(request()->filled($preserved))
                    <input type="hidden" name="{{ $preserved }}" value="{{ request($preserved) }}">
                @endif
            @endforeach

            <div class="mo-field">
                <label for="mo-contact-download-branch">Cabang</label>
                @if($downloadIsSupervisor)
                    <input type="hidden" name="branch_id" value="{{ $downloadBranchId }}">
                    <select id="mo-contact-download-branch" class="mo-select" data-download-branch disabled>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" selected>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="mo-form-help">Cabang otomatis mengikuti cabang yang Anda kelola.</div>
                @else
                    <select id="mo-contact-download-branch" name="branch_id" class="mo-select" data-download-branch>
                        <option value="">— Semua Cabang —</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $downloadBranchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="mo-field">
                <label for="mo-contact-download-agen">Agen</label>
                <select id="mo-contact-download-agen" name="agen_id" class="mo-select" data-download-agen>
                    <option value="">— Semua Agen —</option>
                    @foreach($agents as $agen)
                        <option value="{{ $agen->id }}" data-branch="{{ $agen->branch_id }}" {{ (string) $downloadAgenId === (string) $agen->id ? 'selected' : '' }}>{{ $agen->name }}</option>
                    @endforeach
                </select>
            </div>

            <div data-download-count style="margin-top:12px;padding:10px 14px;border-radius:10px;background:#f0f7f6;color:#00544c;font-size:13px;font-weight:600;">
                Menghitung kontak...
            </div>

            <button type="submit" class="mo-btn mo-btn-primary" style="width:100%;margin-top:12px;" data-download-submit>
                <i class="fas fa-file-excel"></i> Download Kontak
            </button>
        </form>
    </div>
</div>
