@extends('layouts.app')

@section('title', 'Manajemen Program')

@php
    $sort = request('sort', 'name');
    $dir = request('dir', 'asc');
    $sortUrl = function ($key) use ($sort, $dir) {
        $query = request()->except(['page']);
        $query['sort'] = $key;
        if ($sort === $key) {
            $query['dir'] = $dir === 'asc' ? 'desc' : 'asc';
        } else {
            $query['dir'] = in_array($key, ['collected', 'donations'], true) ? 'desc' : 'asc';
        }
        return route('programs.index', $query);
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
        <h1><i class="fas fa-file-invoice-dollar"></i> Manajemen Program Wakaf</h1>
        <p class="subtitle">Kelola program wakaf dan goal keuangan.</p>
    </div>
    <a href="{{ route('programs.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Program</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('programs.index') }}" class="filter-bar">
        <div class="form-group">
            <div class="input-icon">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Cari nama program..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="form-group">
            <select name="program_category">
                <option value="">Semua Kategori</option>
                @foreach(\App\Models\Program::CATEGORIES as $code => $label)
                    <option value="{{ $code }}" {{ request('program_category') == $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <select name="tag">
                <option value="">Semua Tags</option>
                @foreach($allTags as $tag)
                    <option value="{{ $tag->id }}" {{ request('tag') == $tag->id ? 'selected' : '' }}>{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        @if(request()->exists('search') || request()->exists('program_category') || request()->exists('tag') || request()->exists('status'))
            <a href="{{ route('programs.index') }}" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('name') }}" class="sort-link">Program {!! $sortIcon('name') !!}</a></th>
                    <th>Kategori Program</th>
                    <th>Tags</th>
                    <th><a href="{{ $sortUrl('collected') }}" class="sort-link">Terkumpul {!! $sortIcon('collected') !!}</a></th>
                    <th>Progress</th>
                    <th><a href="{{ $sortUrl('donations') }}" class="sort-link">Donasi {!! $sortIcon('donations') !!}</a></th>
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
                            @if($program->program_category)
                                <span class="badge">{{ $program->category_label }}</span>
                                <small style="display:block; color: var(--gray-500);">{{ $program->program_category }}</small>
                            @else
                                <span class="badge badge-gray">-</span>
                            @endif
                        </td>
                        <td>
                            @foreach($program->campaignTags as $tag)
                                <span class="tag-pill" style="background: {{ $tag->color }}; margin-bottom:2px;">{{ $tag->name }}</span>
                            @endforeach
                            @if($program->campaignTags->isEmpty())
                                <span class="badge badge-gray">-</span>
                            @endif
                        </td>
                        <td>Rp {{ number_format($program->total_collected, 0, ',', '.') }}</td>
                        <td><span class="progress-percent">{{ $progress }}%</span></td>
                        <td>{{ $program->donation_items_count }}</td>
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
