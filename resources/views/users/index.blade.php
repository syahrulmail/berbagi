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

<div class="card">
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
        <div class="form-group">
            <input type="search" name="search" placeholder="Cari nama / email / username..." value="{{ request('search') }}">
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
                        <td><strong>{{ $user->name }}</strong></td>
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
