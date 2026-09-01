{{--
    Select yang bisa dicari dengan mengetik.
    Parameters:
    - $name           : nama field hidden input (dikirim ke server)
    - $options        : koleksi model yang akan ditampilkan
    - $valueField     : field untuk value (default 'id')
    - $labelField     : field untuk label utama
    - $suffixField    : opsional, field tambahan ditampilkan dalam kurung (mis. phone)
    - $categoryField  : opsional, field untuk data-category (untuk filter kategori)
    - $selectedValue  : value yang sedang terpilih
    - $selectedLabel  : label terpilih (untuk tampilan awal)
    - $placeholder    : teks placeholder
    - $allowEmpty     : true jika nilai kosong diperbolehkan
--}}
@php
    $allowEmpty = $allowEmpty ?? false;
    $suffixField = $suffixField ?? null;
    $categoryField = $categoryField ?? null;
@endphp
<div class="searchable-select" data-searchable-select data-allow-empty="{{ $allowEmpty ? '1' : '0' }}">
    <input type="text" class="searchable-select-input" placeholder="{{ $placeholder }}" autocomplete="off" value="{{ $selectedLabel }}" {{ $allowEmpty ? '' : 'required' }}>
    <input type="hidden" name="{{ $name }}" value="{{ $selectedValue }}">
    <ul class="searchable-select-list">
        @if($allowEmpty)
            <li class="searchable-empty" data-value=""><em>— Tanpa kontak terhubung —</em></li>
        @endif
        @foreach($options as $opt)
            <li data-value="{{ $opt->{$valueField} }}"
                data-search="{{ $opt->{$labelField} }}{{ $suffixField && $opt->{$suffixField} ? ' ' . $opt->{$suffixField} : '' }}"
                data-phone="{{ $suffixField && $opt->{$suffixField} ? preg_replace('/\D+/', '', $opt->{$suffixField}) : '' }}"
                {!! $categoryField && $opt->{$categoryField} ? 'data-category="' . e($opt->{$categoryField}) . '"' : '' !!}>
                {{ $opt->{$labelField} }}{{ $suffixField && $opt->{$suffixField} ? ' (' . $opt->{$suffixField} . ')' : '' }}
            </li>
        @endforeach
    </ul>
</div>
