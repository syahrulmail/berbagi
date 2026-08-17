@extends('layouts.app')

@section('title', 'Follow-up WhatsApp')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fab fa-whatsapp"></i> Follow-up WhatsApp</h1>
        <p class="subtitle">Rekam klik tombol WhatsApp di halaman publik.</p>
    </div>
</div>

<div class="card">
    @if(auth()->user()->role != 'agen')
    <form method="GET" action="{{ route('followups.index') }}" class="filter-bar">
        <div class="form-group">
            <select name="agen_id">
                <option value="">Semua Agen</option>
                @foreach($agens as $agen)
                    <option value="{{ $agen->id }}" {{ request('agen_id') == $agen->id ? 'selected' : '' }}>
                        {{ $agen->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <select name="program_id">
                <option value="">Semua Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <select name="source">
                <option value="">Semua Sumber</option>
                <option value="agent" {{ request('source') == 'agent' ? 'selected' : '' }}>Halaman Agen</option>
                <option value="program" {{ request('source') == 'program' ? 'selected' : '' }}>Detail Program</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>
    @endif

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Agen</th>
                    <th>Program</th>
                    <th>Sumber</th>
                    <th>Nomor WA</th>
                    <th>IP</th>
                    @if(auth()->user()->role != 'agen')
                    <th class="text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($followups as $fu)
                    <tr>
                        <td>{{ $fu->created_at->format('d M Y H:i') }}</td>
                        <td><strong>{{ $fu->agen->name ?? '-' }}</strong></td>
                        <td>{{ $fu->program->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-green">{{ ucfirst(str_replace('_', ' ', $fu->source)) }}</span>
                        </td>
                        <td>{{ $fu->phone ?: '-' }}</td>
                        <td>{{ $fu->ip_address ?: '-' }}</td>
                        @if(auth()->user()->role != 'agen')
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('followups.destroy', $fu) }}"
                                      onsubmit="return confirm('Yakin menghapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fab fa-whatsapp"></i>
                            <p>Belum ada data follow-up.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $followups->links() }}
</div>
@endsection
