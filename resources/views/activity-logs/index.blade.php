@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-clipboard-list"></i> Log Aktivitas</h1>
        <p class="subtitle">Riwayat aktivitas pengguna di sistem.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td><strong>{{ $log->user->name ?? 'Sistem' }}</strong></td>
                        <td>
                            <span class="badge badge-blue">{{ $log->action }}</span>
                        </td>
                        <td>{{ $log->description ?? '-' }}</td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p>Belum ada aktivitas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
