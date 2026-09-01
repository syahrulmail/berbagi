@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-users"></i> Manajemen Pengguna</h1>
        <p class="subtitle">Kelola Admin, Supervisor, dan Agen.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pengguna</a>
</div>

<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-users"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Pengguna</div>
            <div class="metric-value">{{ $stats['total'] }}</div>
            <div class="metric-sub">{{ $stats['active'] }} aktif</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon red"><i class="fas fa-user-shield"></i></div>
        <div class="metric-info">
            <div class="metric-label">Admin Super</div>
            <div class="metric-value">{{ $stats['admin'] }}</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon orange"><i class="fas fa-user-tie"></i></div>
        <div class="metric-info">
            <div class="metric-label">Supervisor</div>
            <div class="metric-value">{{ $stats['supervisor'] }}</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon green"><i class="fas fa-user-check"></i></div>
        <div class="metric-info">
            <div class="metric-label">Agen</div>
            <div class="metric-value">{{ $stats['agen'] }}</div>
        </div>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
        <div class="form-group">
            <div class="input-icon">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Cari nama / email / username..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="form-group">
            <select name="role">
                <option value="">Semua Role</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin Super</option>
                <option value="supervisor" {{ request('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                <option value="agen" {{ request('role') == 'agen' ? 'selected' : '' }}>Agen</option>
                <option value="donatur" {{ request('role') == 'donatur' ? 'selected' : '' }}>Donatur</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        @if(request('search') || request('role'))
            <a href="{{ route('users.index') }}" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Cabang</th>
                    <th>Link Agen</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="user-avatar" style="width:34px;height:34px;font-size:13px;flex-shrink:0;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @php
                                $roleColors = [
                                    'admin' => 'badge-red',
                                    'supervisor' => 'badge-blue',
                                    'agen' => 'badge-green',
                                    'donatur' => 'badge-orange',
                                ];
                            @endphp
                            <span class="badge {{ $roleColors[$user->role] ?? 'badge-gray' }}">{{ $user->roleLabel() }}</span>
                        </td>
                        <td>{{ $user->branch->name ?? '-' }}</td>
                        <td>
                            @if($user->role == 'agen' || $user->role == 'supervisor')
                                <button type="button" class="btn btn-sm btn-outline" title="Salin link agen"
                                        onclick="copyAgentLink('{{ $user->slug }}')">
                                    <i class="fas fa-link"></i> Salin
                                </button>
                            @else
                                <span style="color: var(--gray-300);">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                @if(auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          onsubmit="return confirm('Yakin menghapus {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <p>Tidak ada pengguna ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection

@push('scripts')
<script>
    function copyAgentLink(slug) {
        var url = '{{ config('app.url') }}/cs/' + slug;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                toast('Link agen disalin: ' + url);
            });
        }
    }
    function toast(msg) {
        var el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:var(--primary-dark);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;z-index:999;box-shadow:var(--shadow-lg);';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 2600);
    }
</script>
@endpush
