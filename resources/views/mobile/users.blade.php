@extends('mobile.layouts.app')

@section('title', 'Pengguna')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title">Pengguna</h1>
            <div class="mo-appbar-sub">{{ $users->count() }} akun terdaftar</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @php
        $roleCls = ['admin' => 'red', 'supervisor' => 'blue', 'agen' => 'green', 'donatur' => 'gray'];
        $roleIcon = ['admin' => 'fa-user-shield', 'supervisor' => 'fa-user-tie', 'agen' => 'fa-user', 'donatur' => 'fa-hand-holding-heart'];
    @endphp
    <div class="mo-list">
        @forelse($users as $u)
            <a href="{{ route('mo.user.edit', $u['id']) }}" style="text-decoration:none;color:inherit;display:block;">
                <div class="mo-row" style="cursor:pointer;">
                    <div class="mo-row-icon {{ $u['role'] === 'admin' ? 'red' : ($u['role'] === 'supervisor' ? 'blue' : '') }}">
                        {{ $u['initial'] }}
                    </div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $u['name'] }}</div>
                        <div class="mo-row-sub"><i class="fas {{ $roleIcon[$u['role']] ?? 'fa-user' }}"></i> {{ $u['branch'] }}</div>
                    </div>
                    <div class="mo-row-end">
                        <span class="mo-badge {{ $roleCls[$u['role']] ?? 'gray' }}">{{ $u['role_label'] }}</span>
                        @if(!$u['is_active'])
                            <div class="mo-badge gray" style="margin-top:4px;">Nonaktif</div>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="mo-empty">
                <i class="fas fa-users"></i>
                <p>Belum ada pengguna.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.user.create') }}" class="mo-fab" aria-label="Tambah Pengguna">
    <i class="fas fa-plus"></i>
</a>
@endsection
