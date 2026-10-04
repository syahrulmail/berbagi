@extends('mobile.layouts.app')

@section('title', $tag ? 'Edit Label' : 'Tambah Label')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.campaign-tags') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title">{{ $tag ? 'Edit Label' : 'Tambah Label' }}</h1>
            <div class="mo-appbar-sub">Label kampanye program</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ $tag ? route('mo.campaign-tags.update', $tag->id) : route('mo.campaign-tags.store') }}" class="mo-form">
        @csrf
        @if($tag)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-tag"></i> Detail Label</div>

            <div class="mo-field">
                <label>Nama Label <span class="req">*</span></label>
                <input type="text" name="name" class="mo-input" value="{{ old('name', $tag->name ?? '') }}" placeholder="Bantuan Ummat" required>
                <div class="mo-form-help">Pisahkan dengan koma untuk membuat beberapa label sekaligus.</div>
                @error('name')<div class="mo-form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>

            @if($tag)
                <div class="mo-field">
                    <label>Slug</label>
                    <input type="text" name="slug" class="mo-input" value="{{ old('slug', $tag->slug ?? '') }}" placeholder="bantuan-ummat">
                    @error('slug')<div class="mo-form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
                </div>
            @endif

            <div class="mo-field">
                <label>Warna <span class="req">*</span></label>
                <input type="color" name="color" class="mo-input" value="{{ old('color', $tag->color ?? '#086e66') }}" style="height:48px;padding:4px;" required>
                @error('color')<div class="mo-form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.campaign-tags') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-floppy-disk"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection
