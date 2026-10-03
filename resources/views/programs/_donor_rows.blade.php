{{--
    Tabel daftar kontak donatur sebuah program (dipakai di dalam modal program-donors).
    Variabel: $donors (Collection dengan id, name, phone, agen_name, donation_count, total_amount).
--}}
@if($donors->isEmpty())
    <div class="empty-state">
        <i class="fas fa-user-slash"></i>
        <p>Belum ada donatur untuk program ini.</p>
    </div>
@else
    <table class="detail-items-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Agen</th>
                <th class="text-center">Jml</th>
                <th>Nominal</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donors as $donor)
                <tr>
                    <td>
                        <strong>{{ $donor->name }}</strong>
                        @if($donor->phone)
                            <small style="display:block; color: var(--gray-500);">{{ $donor->phone }}</small>
                        @endif
                    </td>
                    <td>{{ $donor->agen_name ?? '-' }}</td>
                    <td class="text-center">{{ (int) $donor->donation_count }}x</td>
                    <td class="detail-amount">Rp {{ number_format((float) $donor->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">
                        <button type="button" class="btn btn-sm btn-icon" data-contact-detail="{{ $donor->id }}" title="Lihat Detail Kontak">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
