@extends('layouts.app')

@section('title', 'Edit Banner')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-window-restore"></i> Edit Banner</h1>
        <p class="subtitle">{{ $banner->title }}</p>
    </div>
    <a href="{{ route('popup-banners.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 860px;">
    @include('popup-banners._form', [
        'action' => route('popup-banners.update', $banner),
        'method' => 'PUT',
    ])
</div>
@endsection
