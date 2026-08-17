@extends('layouts.app')

@section('title', 'Manajemen Program')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-invoice-dollar"></i> Manajemen Program Wakaf</h1>
        <p class="subtitle">Kelola program wakaf dan goal keuangan.</p>
    </div>
    <a href="{{ route('programs.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Program</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Tags</th>
                    <th>Goal</th>
                    <th>Terkumpul</th>
                    <th>Progress</th>
                    <th>Donasi</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                    @php
                        $progress = $program->goal_amount > 0 ? min(100, round(((float) $program->total_collected / (float) $program->goal_amount) * 100, 1)) : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $program->name }}</strong></td>
                        <td>
                            @foreach($program->campaignTags as $tag)
                                <span class="tag-pill" style="background: {{ $tag->color }}; margin-bottom:2px;">{{ $tag->name }}</span>
                            @endforeach
                            @if($program->campaignTags->isEmpty())
                                <span class="badge badge-gray">-</span>
                            @endif
                        </td>
                        <td>Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($program->total_collected, 0, ',', '.') }}</td>
                        <td style="min-width: 160px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="progress-track" style="flex:1;"><div class="progress-fill" style="width: {{ $progress }}%"></div></div>
                                <span class="progress-percent">{{ $progress }}%</span>
                            </div>
                        </td>
                        <td>{{ $program->donations_count }}</td>
                        <td>
                            <span class="badge {{ $program->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $program->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('programs.edit', $program) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('programs.destroy', $program) }}"
                                      onsubmit="return confirm('Yakin menghapus program {{ $program->name }}?')">
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
                            <i class="fas fa-file-invoice-dollar"></i>
                            <p>Belum ada program.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $programs->links() }}
</div>
@endsection
