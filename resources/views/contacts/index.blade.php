@extends('layouts.app')

@section('title', 'Manajemen Kontak')

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
                <option value="prospect" {{ request('status') == 'prospect' ? 'selected' : '' }}>Prospect</option>
                <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Contacted</option>
                <option value="donated" {{ request('status') == 'donated' ? 'selected' : '' }}>Donated</option>
                <option value="churned" {{ request('status') == 'churned' ? 'selected' : '' }}>Churned</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>No. WhatsApp</th>
                    <th>Status</th>
                    <th>Agen</th>
                    <th>Cabang</th>
                    <th>Catatan</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td><strong>{{ $contact->name }}</strong></td>
                        <td>{{ $contact->phone }}</td>
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
                        <td>{{ $contact->agen->name ?? '-' }}</td>
                        <td>{{ $contact->branch->name ?? '-' }}</td>
                        <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $contact->notes ?? '-' }}
                        </td>
                        <td>
                            <div class="actions">
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
                        <td colspan="7" class="empty-state">
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
@endsection
