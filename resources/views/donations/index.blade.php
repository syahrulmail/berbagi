@extends('layouts.app')

@section('title', 'Manajemen Donasi')

@php
    $sort = request('sort', 'date');
    $dir = request('dir', 'desc');
    $sortDefaultDir = function ($key) {
        return in_array($key, ['date', 'amount'], true) ? 'desc' : 'asc';
    };
    $sortUrl = function ($key) use ($sort, $dir, $sortDefaultDir) {
        $query = request()->except(['page']);
        $query['sort'] = $key;
        if ($sort === $key) {
            $query['dir'] = $dir === 'asc' ? 'desc' : 'asc';
        } else {
            $query['dir'] = $sortDefaultDir($key);
        }
        return route('donations.index', $query);
    };
    $sortIcon = function ($key) use ($sort, $dir) {
        if ($sort !== $key) {
            return '<i class="fas fa-sort" style="opacity:.4; font-size:11px;"></i>';
        }
        return $dir === 'asc'
            ? '<i class="fas fa-sort-up"></i>'
            : '<i class="fas fa-sort-down"></i>';
    };
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-hand-holding-dollar"></i> Manajemen Donasi</h1>
        <p class="subtitle">Total tampilan filter: <strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></p>
    </div>
    <a href="{{ route('donations.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Catat Donasi</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('donations.index') }}" class="filter-bar">
        <div class="form-group">
            <div class="input-icon">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Cari program, donatur, kontak donatur..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="form-group">
            <label>Dari</label>
            <input type="date" name="from" value="{{ request('from') }}">
        </div>
        <div class="form-group">
            <label>Sampai</label>
            <input type="date" name="to" value="{{ request('to') }}">
        </div>
        <div class="form-group">
            <label>Cabang</label>
            <select name="branch_id">
                <option value="">Semua Cabang</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        @if(request('search'))
            <a href="{{ route('donations.index') }}" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('date') }}" class="sort-link">Tanggal {!! $sortIcon('date') !!}</a></th>
                    <th><a href="{{ $sortUrl('branch') }}" class="sort-link">Cabang {!! $sortIcon('branch') !!}</a></th>
                    <th><a href="{{ $sortUrl('agent') }}" class="sort-link">Agen {!! $sortIcon('agent') !!}</a></th>
                    <th><a href="{{ $sortUrl('program') }}" class="sort-link">Program Donasi {!! $sortIcon('program') !!}</a></th>
                    <th><a href="{{ $sortUrl('donatur') }}" class="sort-link">Donatur {!! $sortIcon('donatur') !!}</a></th>
                    <th>Kontak Donatur</th>
                    <th><a href="{{ $sortUrl('amount') }}" class="sort-link">Nominal {!! $sortIcon('amount') !!}</a></th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donations as $donation)
                    <tr>
                        <td>{{ $donation->donation_date->format('d M Y') }}</td>
                        <td>{{ $donation->branch->name ?? '-' }}</td>
                        <td>{{ $donation->agen->name ?? '-' }}</td>
                        <td>
                            @if($donation->items->isNotEmpty())
                                <div class="item-list">
                                    @foreach($donation->items as $item)
                                        <span class="item-program-line">
                                            @if($item->program && $item->program->program_category)
                                                <span class="badge">{{ $item->program->category_label ?: '-' }}</span>
                                            @endif
                                            <span>{{ $item->program->name ?? '-' }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @elseif($donation->program)
                                <span class="item-program-line">
                                    @if($donation->program->program_category)
                                        <span class="badge">{{ $donation->program->category_label }}</span>
                                    @endif
                                    <span>{{ $donation->program->name }}</span>
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $donation->contact->name ?? '-' }}</td>
                        <td>{{ $donation->contact->phone ?? '-' }}</td>
                        <td><strong>Rp {{ number_format($donation->amount, 0, ',', '.') }}</strong></td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn-sm btn-icon" data-donation-detail="{{ $donation->id }}" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="{{ route('donations.edit', $donation) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('donations.destroy', $donation) }}"
                                      onsubmit="return confirm('Yakin menghapus donasi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-hand-holding-dollar"></i>
                            <p>Belum ada data donasi.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $donations->links() }}
</div>

@include('partials.donation-detail-modal')
@endsection

@push('scripts')
<script src="{{ assetv('js/donation-form.js') }}"></script>
<script src="{{ assetv('js/donation-detail.js') }}"></script>
@endpush
