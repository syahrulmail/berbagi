@extends('mobile.layouts.app')

@section('title', 'Banner')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-images" style="color:var(--mo-primary);font-size:19px;"></i> Banner &amp; Label</h1>
            <div class="mo-appbar-sub">{{ $banners->total() }} item</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-list">
        @forelse($banners as $b)
            <div class="mo-row" style="align-items:flex-start;">
                <div class="mo-row-icon" style="background:#fff;overflow:hidden;padding:0;">
                    @if($b->image_url)
                        <img src="{{ $b->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <i class="fas {{ $b->type === 'label' ? 'fa-tag' : 'fa-image' }}"></i>
                    @endif
                </div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $b->title }}</div>
                    <div class="mo-row-sub">Urutan {{ $b->sort_order }} · {{ $b->type === 'label' ? 'Label' : 'Banner' }}</div>
                    <div style="margin-top:6px;display:flex;align-items:center;gap:8px;">
                        <span class="mo-badge {{ $b->is_active ? 'green' : 'gray' }}">{{ $b->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                </div>
                <div class="mo-row-end" style="display:flex;gap:6px;">
                    <a href="{{ route('mo.banners.edit', $b->id) }}" class="mo-icon-btn" style="width:36px;height:36px;" aria-label="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('mo.banners.destroy', $b->id) }}" onsubmit="return confirm('Hapus banner {{ $b->title }}?');" style="margin:0;">
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
                <i class="fas fa-images"></i>
                <p>Belum ada banner.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $banners])
</div>

<a href="{{ route('mo.banners.create') }}" class="mo-fab" aria-label="Tambah banner"><i class="fas fa-plus"></i></a>
@endsection
