{{--
    Baris item program donasi (untuk form multi-program).
    Parameters:
    - $index            : indeks baris (default 0)
    - $rowProgramId     : program yang terpilih
    - $rowCategory      : kategori program terpilih
    - $rowAmount        : nominal donasi
    Variabel parent yang dibutuhkan: $programs (koleksi program aktif)
--}}
@php
    $rowIndex = $index ?? 0;
    $rowProgramId = $rowProgramId ?? '';
    $rowCategory = $rowCategory ?? '';
    $rowAmount = $rowAmount ?? '';
    $rowProgramModel = $programs->firstWhere('id', $rowProgramId);
    $rowProgramLabel = $rowProgramModel ? $rowProgramModel->name : '';
@endphp
<div class="donation-item-row" data-item-row>
    <div class="form-group">
        <label>Kategori Program</label>
        <select name="items[{{ $rowIndex }}][program_category]" class="item-category">
            <option value="">— Semua Kategori —</option>
            @foreach(\App\Models\Program::CATEGORIES as $categoryKey => $categoryLabel)
                <option value="{{ $categoryKey }}" {{ $rowCategory == $categoryKey ? 'selected' : '' }}>{{ $categoryLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Program *</label>
        @include('partials.searchable-select', [
            'name' => 'items[' . $rowIndex . '][program_id]',
            'options' => $programs,
            'valueField' => 'id',
            'labelField' => 'name',
            'categoryField' => 'program_category',
            'selectedValue' => $rowProgramId,
            'selectedLabel' => $rowProgramLabel,
            'placeholder' => 'Ketik untuk mencari program...',
        ])
    </div>
    <div class="form-group">
        <label>Nominal (Rp) *</label>
        <input type="number" name="items[{{ $rowIndex }}][amount]" class="item-amount"
               value="{{ $rowAmount }}" min="1" step="0.01" required placeholder="Nominal">
    </div>
    <button type="button" class="btn btn-sm btn-icon btn-danger item-remove" title="Hapus Program">
        <i class="fas fa-trash"></i>
    </button>
</div>
