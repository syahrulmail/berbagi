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

    <div class="mo-sticky-filter">
        <form method="GET" action="{{ route('mo.users') }}" id="mo-user-form">
            <div class="mo-search-flex">
                <button type="button" class="mo-filter-toggle {{ $hasFilter ? 'has-filter' : '' }}" id="mo-user-filter-toggle"
                        data-filter-toggle="mo-user-filters" aria-label="Cari &amp; filter pengguna" aria-expanded="false">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
            </div>

            <div id="mo-user-filters" hidden>
                <div class="mo-search" style="margin-top:10px;">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="search" placeholder="Cari nama / nomor HP..." value="{{ request('search') }}">
                </div>

                @if(auth()->user()->isAdmin())
                    <div class="mo-field">
                        <label for="mo-user-branch">Cabang</label>
                        <select id="mo-user-branch" name="branch_id" class="mo-select">
                            <option value="">— Semua Cabang —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div style="display:flex;gap:10px;">
                    <div class="mo-field" style="flex:1;">
                        <label for="mo-user-from">Dari Tanggal</label>
                        <input type="date" id="mo-user-from" name="from" class="mo-input" value="{{ request('from') }}">
                    </div>
                    <div class="mo-field" style="flex:1;">
                        <label for="mo-user-to">Sampai Tanggal</label>
                        <input type="date" id="mo-user-to" name="to" class="mo-input" value="{{ request('to') }}">
                    </div>
                </div>

                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <div class="mo-segmented" style="margin-bottom:10px;">
                    <button type="submit" name="sort" value="" class="mo-segmented-item {{ !$sortDonation ? 'active' : '' }}">
                        <i class="fas fa-clock"></i> Terbaru
                    </button>
                    <button type="submit" name="sort" value="donation" class="mo-segmented-item {{ $sortDonation ? 'active' : '' }}">
                        <i class="fas fa-arrow-down-wide-short"></i> Donasi Terbesar
                    </button>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="mo-btn mo-btn-primary" style="flex:1;"><i class="fas fa-check"></i> Terapkan</button>
                    @if($hasFilter)
                        <a href="{{ route('mo.users') }}" class="mo-btn mo-btn-ghost" style="flex:0 0 auto;" aria-label="Reset filter">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="mo-list">
        @forelse($users as $u)
            <div class="mo-row" data-user-detail="{{ $u['id'] }}">
                <div class="mo-row-icon {{ $u['role'] === 'admin' ? 'red' : ($u['role'] === 'supervisor' ? 'blue' : '') }}" style="overflow:hidden;">
                    @if($u['photo_url'])
                        <img src="{{ $u['photo_url'] }}" alt="{{ $u['name'] }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ $u['initial'] }}
                    @endif
                </div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $u['name'] }}</div>
                    <div class="mo-row-sub"><i class="fas {{ $roleIcon[$u['role']] ?? 'fa-user' }}"></i> {{ $u['branch'] }}</div>
                    <div class="mo-row-donation">
                        @if($u['donation_meta'])
                            <i class="fas fa-hand-holding-dollar"></i>
                            <span class="amount">{{ $u['donation_total_formatted'] }}</span>
                            <span class="date">dari {{ $u['donation_meta'] }}</span>
                        @else
                            <span class="empty">Belum ada donasi</span>
                        @endif
                    </div>
                </div>
                <div class="mo-row-end">
                    <span class="mo-badge {{ $roleCls[$u['role']] ?? 'gray' }}">{{ $u['role_label'] }}</span>
                    @if(!$u['is_active'])
                        <div class="mo-badge gray" style="margin-top:4px;">Nonaktif</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-users"></i>
                <p>Belum ada pengguna{{ request('search') ? ' sesuai pencarian' : '' }}.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.user.create') }}" class="mo-fab" aria-label="Tambah Pengguna">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.user-sheet')
@endsection
