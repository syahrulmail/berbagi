@extends('layouts.app')

@section('title', 'Manajemen Donasi')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-hand-holding-dollar"></i> Manajemen Donasi</h1>
        <p class="subtitle">Total tampilan filter: <strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></p>
    </div>
    <a href="{{ route('donations.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Catat Donasi</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('donations.index') }}" class="filter-bar">
        <div class="form-group">
            <label>Dari</label>
            <input type="date" name="from" value="{{ request('from') }}">
        </div>
        <div class="form-group">
            <label>Sampai</label>
            <input type="date" name="to" value="{{ request('to') }}">
        </div>
        <div class="form-group">
            <label>Cabang</label>
            <select name="branch_id">
                <option value="">Semua Cabang</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Program</label>
            <select name="program_id">
                <option value="">Semua Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Agen</th>
                    <th>Program</th>
                    <th>Donatur</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donations as $donation)
                    <tr>
                        <td>{{ $donation->donation_date->format('d M Y') }}</td>
                        <td>{{ $donation->branch->name ?? '-' }}</td>
                        <td>{{ $donation->agen->name ?? '-' }}</td>
                        <td>{{ $donation->program->name ?? '-' }}</td>
                        <td>{{ $donation->contact->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-blue">{{ strtoupper(str_replace('_', ' ', $donation->payment_method)) }}</span>
                        </td>
                        <td><strong>Rp {{ number_format($donation->amount, 0, ',', '.') }}</strong></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('donations.edit', $donation) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('donations.destroy', $donation) }}"
                                      onsubmit="return confirm('Yakin menghapus donasi ini?')">
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
                            <i class="fas fa-hand-holding-dollar"></i>
                            <p>Belum ada data donasi.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $donations->links() }}
</div>
@endsection
