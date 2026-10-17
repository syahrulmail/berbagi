@extends('layouts.app')

@section('title', 'Banner')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-window-restore"></i> Banner</h1>
        <p class="subtitle">Banner popup untuk halaman publik dan widget statistik kecil di kiri bawah.</p>
    </div>
    <a href="{{ route('popup-banners.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Banner</a>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <h2 style="font-size:16px; font-weight:700; margin:0 0 4px;">Popup Statistik Otomatis</h2>
            <p style="margin:0; color:var(--gray-500); font-size:13px; max-width:640px;">
                Kartu kecil di kiri bawah halaman publik. Tampil 3 detik lalu menutup sendiri, kemudian muncul lagi
                bergantian setiap 2–60 detik. Isinya dipilih acak dari data donasi per kategori program
                (hari ini, kemarin, pekan ini, bulan ini) atau data pencapaian.
            </p>
        </div>
        <span class="badge {{ $statsSettings['enabled'] ? 'badge-green' : 'badge-gray' }}">
            {{ $statsSettings['enabled'] ? 'Aktif' : 'Nonaktif' }}
        </span>
    </div>

    <form method="POST" action="{{ route('popup-banners.settings') }}" style="margin-top:16px; display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap;">
        @csrf
        @method('PUT')
        <div class="form-group" style="margin:0;">
            <label class="checkbox-label" style="margin:0;">
                <input type="checkbox" name="popup_stats_enabled" value="1" {{ $statsSettings['enabled'] ? 'checked' : '' }}>
                Tampilkan popup statistik
            </label>
        </div>
        <div class="form-group" style="margin:0; max-width:200px;">
            <label for="popup_stats_delay_seconds">Tampil pertama setelah (detik)</label>
            <input type="number" id="popup_stats_delay_seconds" name="popup_stats_delay_seconds"
                   value="{{ old('popup_stats_delay_seconds', $statsSettings['delay_seconds']) }}" min="0" max="300">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Setelan</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Judul</th>
                    <th>Jadwal</th>
                    <th>Halaman</th>
                    <th>Agen</th>
                    <th>Pemicu</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $banner)
                    <tr>
                        <td>
                            @if($banner->image)
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                                     style="height:56px; max-width:96px; object-fit:contain; border-radius:6px; background:#f8fafc;">
                            @else
                                <span class="badge badge-gray">Tanpa gambar</span>
                            @endif
                        </td>
                        <td><strong>{{ $banner->title }}</strong></td>
                        <td style="font-size:13px;">{{ $banner->schedule_label }}</td>
                        <td style="font-size:13px;">{{ $banner->pages_label }}</td>
                        <td style="font-size:13px;">{{ $banner->agent_scope_label }}</td>
                        <td style="font-size:13px;">{{ $banner->trigger_label }}</td>
                        <td>
                            <span class="badge {{ $banner->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('popup-banners.edit', $banner) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('popup-banners.destroy', $banner) }}"
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
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-window-restore"></i>
                            <p>Belum ada banner popup.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $banners->links() }}
</div>
@endsection
