@extends('mobile.layouts.app')

@section('title', 'Label Kampanye')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-tags" style="color:var(--mo-primary);font-size:19px;"></i> Label Kampanye</h1>
            <div class="mo-appbar-sub">{{ $tags->total() }} label</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-list">
        @forelse($tags as $tag)
            <div class="mo-row">
                <div class="mo-row-icon" style="background:{{ $tag->color }};color:#fff;"><i class="fas fa-tag"></i></div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $tag->name }}</div>
                    <div class="mo-row-sub">{{ $tag->slug }} · {{ $tag->programs_count }} program</div>
                </div>
                <div class="mo-row-end" style="display:flex;gap:6px;">
                    <a href="{{ route('mo.campaign-tags.edit', $tag->id) }}" class="mo-icon-btn" style="width:36px;height:36px;" aria-label="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('mo.campaign-tags.destroy', $tag->id) }}" onsubmit="return confirm('Hapus label {{ $tag->name }}?');" style="margin:0;">
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
                <i class="fas fa-tags"></i>
                <p>Belum ada label kampanye.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $tags])
</div>

<a href="{{ route('mo.campaign-tags.create') }}" class="mo-fab" aria-label="Tambah label"><i class="fas fa-plus"></i></a>
@endsection
