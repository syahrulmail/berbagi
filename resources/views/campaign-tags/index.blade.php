@extends('layouts.app')

@section('title', 'Label Kampanye')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-tags"></i> Label Kampanye</h1>
        <p class="subtitle">Label untuk pengelompokan program wakaf.</p>
    </div>
    <a href="{{ route('campaign-tags.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Label</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Warna</th>
                    <th>Program Terkait</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                    <tr>
                        <td><strong>{{ $tag->name }}</strong></td>
                        <td><span class="tag-pill" style="background: {{ $tag->color }}">{{ $tag->name }}</span></td>
                        <td>{{ $tag->programs_count }} program</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('campaign-tags.edit', $tag) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('campaign-tags.destroy', $tag) }}"
                                      onsubmit="return confirm('Yakin menghapus label {{ $tag->name }}?')">
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
                        <td colspan="4" class="empty-state">
                            <i class="fas fa-tags"></i>
                            <p>Belum ada label kampanye.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tags->links() }}
</div>
@endsection
