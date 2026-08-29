@extends('layouts.app')

@section('title', 'Pencapaian')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-medal"></i> Pencapaian</h1>
        <p class="subtitle">Kartu pencapaian lembaga yang tampil di halaman beranda dan halaman agen.</p>
    </div>
    <a href="{{ route('achievements.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pencapaian</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Data</th>
                    <th>Keterangan</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($achievements as $achievement)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="width:40px; height:40px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background: {{ $achievement->color }}; color: #fff; font-size:16px; flex:none;">
                                    @if($achievement->image)
                                        <img src="{{ $achievement->image_url }}" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">
                                    @else
                                        <i class="fas {{ $achievement->icon ?: 'fa-trophy' }}"></i>
                                    @endif
                                </span>
                                <span class="badge" style="background: {{ $achievement->color }}">{{ $achievement->color }}</span>
                            </div>
                        </td>
                        <td><strong>{{ $achievement->value }}</strong></td>
                        <td>{{ $achievement->label }}</td>
                        <td>{{ $achievement->sort_order }}</td>
                        <td>
                            <span class="badge {{ $achievement->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $achievement->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('achievements.edit', $achievement) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('achievements.destroy', $achievement) }}"
                                      onsubmit="return confirm('Yakin menghapus pencapaian {{ $achievement->value }}?')">
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
                            <i class="fas fa-medal"></i>
                            <p>Belum ada pencapaian.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $achievements->links() }}
</div>
@endsection
