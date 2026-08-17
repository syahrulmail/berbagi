@extends('layouts.app')

@section('title', 'Banner & Label')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-images"></i> Banner & Label</h1>
        <p class="subtitle">Manajemen banner dan label promosi.</p>
    </div>
    <a href="{{ route('banners.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Banner</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>Gambar</th>
                    <th>Warna Label</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $banner)
                    <tr>
                        <td><strong>{{ $banner->title }}</strong></td>
                        <td>
                            <span class="badge {{ $banner->type == 'banner' ? 'badge-blue' : 'badge-orange' }}">
                                {{ ucfirst($banner->type) }}
                            </span>
                        </td>
                        <td>
                            @if($banner->image)
                                <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}" style="height: 40px; border-radius: 6px;">
                            @else
                                <span class="badge badge-gray">Tanpa gambar</span>
                            @endif
                        </td>
                        <td>
                            @if($banner->label_color)
                                <span class="tag-pill" style="background: {{ $banner->label_color }}">{{ $banner->label_color }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $banner->sort_order }}</td>
                        <td>
                            <span class="badge {{ $banner->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('banners.edit', $banner) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('banners.destroy', $banner) }}"
                                      onsubmit="return confirm('Yakin menghapus banner {{ $banner->title }}?')">
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
                            <i class="fas fa-images"></i>
                            <p>Belum ada banner.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $banners->links() }}
</div>
@endsection
