@extends('layouts.app')

@section('title', 'Tambah Banner')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-window-restore"></i> Tambah Banner</h1>
        <p class="subtitle">Atur gambar, jadwal, halaman, dan pemicu banner popup.</p>
    </div>
    <a href="{{ route('popup-banners.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 860px;">
    @include('popup-banners._form', [
        'action' => route('popup-banners.store'),
        'method' => 'POST',
    ])
</div>
@endsection
