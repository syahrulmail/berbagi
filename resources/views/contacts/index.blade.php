@extends('layouts.app')

@section('title', 'Manajemen Kontak')

@php
    $sort = request('sort', 'created_at');
    $dir = request('dir', 'desc');
    $sortUrl = function ($key) use ($sort, $dir) {
        $query = request()->except(['page']);
        $query['sort'] = $key;
        if ($sort === $key) {
            $query['dir'] = $dir === 'asc' ? 'desc' : 'asc';
        } else {
            $query['dir'] = $key === 'donation' ? 'desc' : 'asc';
        }
        return route('contacts.index', $query);
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
        <h1><i class="fas fa-address-book"></i> Manajemen Kontak</h1>
        <p class="subtitle">Kelola calon donatur (Kontak Intelligent).</p>
    </div>
    <a href="{{ route('contacts.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Kontak</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('contacts.index') }}" class="filter-bar">
        <div class="form-group">
            <input type="search" name="search" placeholder="Cari nama / no. HP..." value="{{ request('search') }}">
        </div>
        <div class="form-group">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="prospect" {{ request('status') == 'prospect' ? 'selected' : '' }}>Prospek</option>
                <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Simpan</option>
                <option value="donated" {{ request('status') == 'donated' ? 'selected' : '' }}>Wakif</option>
                <option value="churned" {{ request('status') == 'churned' ? 'selected' : '' }}>Stop</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('name') }}" class="sort-link">Nama {!! $sortIcon('name') !!}</a></th>
                    <th><a href="{{ $sortUrl('status') }}" class="sort-link">Status {!! $sortIcon('status') !!}</a></th>
                    <th><a href="{{ $sortUrl('agen') }}" class="sort-link">Agen {!! $sortIcon('agen') !!}</a></th>
                    <th><a href="{{ $sortUrl('donation') }}" class="sort-link">Donasi {!! $sortIcon('donation') !!}</a></th>
                    <th>Catatan</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td>
                            <strong>{{ $contact->name }}</strong>
                            <small style="display:block; color: var(--gray-500);">{{ $contact->phone }}</small>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'prospect' => 'badge-blue',
                                    'contacted' => 'badge-orange',
                                    'donated' => 'badge-green',
                                    'churned' => 'badge-red',
                                ];
                            @endphp
                            <span class="badge {{ $statusColors[$contact->status] ?? 'badge-gray' }}">{{ $contact->statusLabel() }}</span>
                        </td>
                        <td>
                            {{ $contact->agen->name ?? '-' }}
                            @if($contact->branch)
                                <small style="display:block; color: var(--gray-500);">{{ $contact->branch->name }}</small>
                            @endif
                        </td>
                        <td>Rp {{ number_format((float) ($contact->total_donation ?? 0), 0, ',', '.') }}</td>
                        <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $contact->notes ?? '-' }}
                        </td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn-sm btn-icon" data-contact-detail="{{ $contact->id }}" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('contacts.destroy', $contact) }}"
                                      onsubmit="return confirm('Yakin menghapus kontak {{ $contact->name }}?')">
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
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-address-book"></i>
                            <p>Belum ada data kontak.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $contacts->links() }}
</div>

@include('partials.contact-detail-modal')
@include('partials.donation-detail-modal')
@endsection

@push('scripts')
<script src="{{ assetv('js/donation-form.js') }}"></script>
<script src="{{ assetv('js/contact-detail.js') }}"></script>
<script src="{{ assetv('js/donation-detail.js') }}"></script>
@endpush
