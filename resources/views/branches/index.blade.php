@extends('layouts.app')

@section('title', 'Manajemen Cabang')

@php
    $sort = request('sort', 'code');
    $dir = request('dir', 'asc');
    $sortDefaultDir = function ($key) {
        return in_array($key, ['terkumpul', 'progress', 'donations', 'status'], true) ? 'desc' : 'asc';
    };
    $sortUrl = function ($key) use ($sort, $dir, $sortDefaultDir) {
        $query = request()->except(['page']);
        $query['sort'] = $key;
        if ($sort === $key) {
            $query['dir'] = $dir === 'asc' ? 'desc' : 'asc';
        } else {
            $query['dir'] = $sortDefaultDir($key);
        }
        return route('branches.index', $query);
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
        <h1><i class="fas fa-building"></i> Manajemen Cabang</h1>
        <p class="subtitle">{{ $branches->total() }} cabang BWA terdaftar.</p>
    </div>
    <a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Cabang</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('branches.index') }}" class="filter-bar">
        <div class="form-group">
            <div class="input-icon">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Cari kode / nama / kota..." value="{{ request('search') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Cari</button>
        @if(request('search'))
            <a href="{{ route('branches.index') }}" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Cabang</th>
                    <th>Supervisor</th>
                    <th><a href="{{ $sortUrl('agents') }}" class="sort-link">Agen {!! $sortIcon('agents') !!}</a></th>
                    <th><a href="{{ $sortUrl('terkumpul') }}" class="sort-link">Terkumpul {!! $sortIcon('terkumpul') !!}</a></th>
                    <th><a href="{{ $sortUrl('progress') }}" class="sort-link">Progress {!! $sortIcon('progress') !!}</a></th>
                    <th><a href="{{ $sortUrl('donations') }}" class="sort-link">Donasi {!! $sortIcon('donations') !!}</a></th>
                    <th><a href="{{ $sortUrl('status') }}" class="sort-link">Status {!! $sortIcon('status') !!}</a></th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    @php
                        $terkumpul = (float) ($branch->donations_sum_amount ?? 0);
                        $progress = (float) $branch->target_amount > 0
                            ? round($terkumpul / (float) $branch->target_amount * 100, 1)
                            : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $branch->code }}</strong></td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->supervisor->name ?? '-' }}</td>
                        <td>{{ $branch->agents_count }}</td>
                        <td>Rp {{ number_format($terkumpul, 0, ',', '.') }}</td>
                        <td>{{ number_format($progress, $progress == (int) $progress ? 0 : 1, ',', '.') }}%</td>
                        <td>{{ $branch->donations_count }}</td>
                        <td>
                            <span class="badge {{ $branch->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                      onsubmit="return confirm('Yakin menghapus cabang {{ $branch->name }}?')">
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
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-building-circle-exclamation"></i>
                            <p>Belum ada data cabang.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $branches->links() }}
</div>
@endsection
