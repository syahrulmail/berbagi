@extends('mobile.layouts.app')

@section('title', 'Pencapaian')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-trophy" style="color:var(--mo-gold);font-size:19px;"></i> Pencapaian</h1>
            <div class="mo-appbar-sub">{{ $achievements->total() }} item</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-list">
        @forelse($achievements as $a)
            <div class="mo-row">
                <div class="mo-row-icon" style="background:{{ $a->color ?: 'var(--mo-gold)' }}22;color:{{ $a->color ?: 'var(--mo-gold)' }};overflow:hidden;">
                    @if($a->image_url)
                        <img src="{{ $a->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <i class="fas {{ $a->icon ?: 'fa-trophy' }}"></i>
                    @endif
                </div>
                <div class="mo-row-body">
                    <div class="mo-row-title" style="color:{{ $a->color ?: 'var(--mo-text)' }};">{{ $a->value }}</div>
                    <div class="mo-row-sub">{{ $a->label }} · urutan {{ $a->sort_order }}</div>
                    <div style="margin-top:6px;">
                        <span class="mo-badge {{ $a->is_active ? 'green' : 'gray' }}">{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                </div>
                <div class="mo-row-end" style="display:flex;gap:6px;">
                    <a href="{{ route('mo.achievements.edit', $a->id) }}" class="mo-icon-btn" style="width:36px;height:36px;" aria-label="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('mo.achievements.destroy', $a->id) }}" onsubmit="return confirm('Hapus pencapaian ini?');" style="margin:0;">
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
                <i class="fas fa-trophy"></i>
                <p>Belum ada pencapaian.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $achievements])
</div>

<a href="{{ route('mo.achievements.create') }}" class="mo-fab" aria-label="Tambah pencapaian"><i class="fas fa-plus"></i></a>
@endsection
