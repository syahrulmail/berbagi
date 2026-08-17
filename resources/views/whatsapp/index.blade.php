@extends('layouts.app')

@section('title', 'WhatsApp')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fab fa-whatsapp"></i> Pesan WhatsApp</h1>
        <p class="subtitle">Antrian pesan otomatis ke kontak donatur.</p>
    </div>
    <a href="{{ route('whatsapp.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Jadwalkan Pesan</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('whatsapp.index') }}" class="filter-bar">
        <div class="form-group">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Gagal</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Tujuan</th>
                    <th>Kontak</th>
                    <th>Pesan</th>
                    <th>Status</th>
                    <th>Dijadwalkan</th>
                    <th>Dikirim</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>{{ $message->phone }}</td>
                        <td>{{ $message->contact->name ?? '-' }}</td>
                        <td style="max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $message->message }}
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'badge-orange',
                                    'sent' => 'badge-green',
                                    'failed' => 'badge-red',
                                ];
                            @endphp
                            <span class="badge {{ $statusColors[$message->status] ?? 'badge-gray' }}">{{ ucfirst($message->status) }}</span>
                        </td>
                        <td>{{ $message->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $message->sent_at ? $message->sent_at->format('d M Y H:i') : '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('whatsapp.destroy', $message) }}"
                                  onsubmit="return confirm('Yakin menghapus pesan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fab fa-whatsapp"></i>
                            <p>Belum ada pesan WhatsApp.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $messages->links() }}
</div>
@endsection
