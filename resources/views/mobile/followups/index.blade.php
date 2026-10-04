@extends('mobile.layouts.app')

@section('title', 'Follow-up WA')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-comments" style="color:var(--mo-primary);font-size:19px;"></i> Follow-up WA</h1>
            <div class="mo-appbar-sub">{{ $followups->total() }} data follow-up</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="GET" action="{{ route('mo.followups') }}" class="mo-card mo-card--flat" style="padding:14px;">
        <div class="mo-field">
            <label>Sumber</label>
            <select name="source" class="mo-select">
                <option value="">Semua sumber</option>
                <option value="home" {{ request('source') === 'home' ? 'selected' : '' }}>Beranda</option>
                <option value="program" {{ request('source') === 'program' ? 'selected' : '' }}>Program</option>
                <option value="agent" {{ request('source') === 'agent' ? 'selected' : '' }}>Agen</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;">
            <div class="mo-field" style="flex:1;">
                <label>Dari</label>
                <input type="date" name="date_from" class="mo-input" value="{{ request('date_from') }}">
            </div>
            <div class="mo-field" style="flex:1;">
                <label>Sampai</label>
                <input type="date" name="date_to" class="mo-input" value="{{ request('date_to') }}">
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mo.followups') }}" class="mo-btn mo-btn-ghost">Reset</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </form>

    <div class="mo-list">
        @forelse($followups as $f)
            @php
                $srcLabel = ['home' => 'Beranda', 'program' => 'Program', 'agent' => 'Agen'][$f->source] ?? $f->source;
            @endphp
            <div class="mo-row" style="align-items:flex-start;">
                <div class="mo-row-icon blue"><i class="fab fa-whatsapp"></i></div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $f->phone }}</div>
                    <div class="mo-row-sub">
                        {{ $f->agen->name ?? '-' }} · {{ $f->program->name ?? 'Tanpa program' }}
                    </div>
                    <div style="margin-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span class="mo-badge teal">{{ $srcLabel }}</span>
                        <span style="font-size:11px;color:#9db3b0;">{{ $f->created_at ? $f->created_at->format('d M Y H:i') : '' }}</span>
                    </div>
                </div>
                @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                    <div class="mo-row-end">
                        <form method="POST" action="{{ route('mo.followups.destroy', $f->id) }}" onsubmit="return confirm('Hapus data follow-up ini?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mo-icon-btn" style="width:36px;height:36px;background:#fdeeec;color:var(--mo-danger);box-shadow:none;" aria-label="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-comments"></i>
                <p>Belum ada data follow-up.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $followups])
</div>
@endsection
