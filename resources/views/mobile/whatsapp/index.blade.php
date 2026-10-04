@extends('mobile.layouts.app')

@section('title', 'WhatsApp')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fab fa-whatsapp" style="color:#25d366;font-size:19px;"></i> WhatsApp</h1>
            <div class="mo-appbar-sub">{{ $statusCounts['all'] }} pesan</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-segmented" style="margin-bottom:14px;">
        <a href="{{ route('mo.whatsapp') }}" class="mo-segmented-item {{ !request('status') ? 'active' : '' }}">Semua ({{ $statusCounts['all'] }})</a>
        <a href="{{ route('mo.whatsapp', ['status' => 'pending']) }}" class="mo-segmented-item {{ request('status') === 'pending' ? 'active' : '' }}">Antrian ({{ $statusCounts['pending'] }})</a>
        <a href="{{ route('mo.whatsapp', ['status' => 'sent']) }}" class="mo-segmented-item {{ request('status') === 'sent' ? 'active' : '' }}">Terkirim</a>
        <a href="{{ route('mo.whatsapp', ['status' => 'failed']) }}" class="mo-segmented-item {{ request('status') === 'failed' ? 'active' : '' }}">Gagal</a>
    </div>

    <div class="mo-list">
        @forelse($messages as $m)
            @php
                $statusCls = ['pending' => 'gold', 'sent' => 'green', 'failed' => 'red'][$m->status] ?? 'gray';
                $statusLabel = ['pending' => 'Antrian', 'sent' => 'Terkirim', 'failed' => 'Gagal'][$m->status] ?? $m->status;
            @endphp
            <div class="mo-row" style="align-items:flex-start;">
                <div class="mo-row-icon {{ $m->status === 'failed' ? 'red' : 'green' }}"><i class="fab fa-whatsapp"></i></div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $m->contact->name ?? $m->phone }}</div>
                    <div class="mo-row-sub">{{ $m->phone }}</div>
                    <div style="font-size:12.5px;color:var(--mo-muted);margin-top:5px;line-height:1.4;">{{ \Illuminate\Support\Str::limit($m->message, 90) }}</div>
                    <div style="margin-top:6px;display:flex;align-items:center;gap:8px;">
                        <span class="mo-badge {{ $statusCls }}">{{ $statusLabel }}</span>
                        <span style="font-size:11px;color:#9db3b0;">{{ $m->created_at ? $m->created_at->format('d M Y H:i') : '' }}</span>
                    </div>
                </div>
                <div class="mo-row-end">
                    <form method="POST" action="{{ route('mo.whatsapp.destroy', $m->id) }}" onsubmit="return confirm('Hapus pesan WhatsApp ini?');" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mo-icon-btn" style="width:36px;height:36px;background:#fdeeec;color:var(--mo-danger);box-shadow:none;" aria-label="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fab fa-whatsapp"></i>
                <p>Belum ada pesan WhatsApp.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $messages])
</div>

<a href="{{ route('mo.whatsapp.create') }}" class="mo-fab" aria-label="Tambah pesan"><i class="fas fa-plus"></i></a>
@endsection
