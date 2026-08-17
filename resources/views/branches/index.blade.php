@extends('layouts.app')

@section('title', 'Manajemen Cabang')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-building"></i> Manajemen Cabang</h1>
        <p class="subtitle">{{ $branches->total() }} cabang BWA terdaftar.</p>
    </div>
    <a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Cabang</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Cabang</th>
                    <th>Kota</th>
                    <th>Supervisor</th>
                    <th>Target</th>
                    <th>Agen</th>
                    <th>Donasi</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td><strong>{{ $branch->code }}</strong></td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->city ?? '-' }}</td>
                        <td>{{ $branch->supervisor->name ?? '-' }}</td>
                        <td>Rp {{ number_format($branch->target_amount, 0, ',', '.') }}</td>
                        <td>{{ $branch->agents_count }}</td>
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
