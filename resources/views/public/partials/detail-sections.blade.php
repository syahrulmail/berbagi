@php
    $achievementList = $achievements->map(function ($a) {
        $parts = $a->numericParts();
        $formatted = $parts['number'] !== null
            ? number_format($parts['number'], $parts['decimals'], ',', '.')
            : trim((string) $a->value);
        $suffix = $parts['suffix'];
        if ($suffix !== '' && preg_match('/^[A-Za-z0-9]/', $suffix)) {
            $suffix = ' ' . $suffix;
        }
        $display = ($parts['prefix'] !== '' ? $parts['prefix'] . ' ' : '') . $formatted . $suffix;
        return [
            'color'   => $a->color ?: '#08A899',
            'icon'    => $a->icon ?: 'fa-trophy',
            'image'   => $a->image_url,
            'display' => $display,
            'label'   => $a->label,
        ];
    });
@endphp

@if(isset($achievements) && $achievementList->isNotEmpty())
<section class="mt-14" data-reveal>
    <h2 class="text-xl font-bold text-primary-900">Pencapaian</h2>
    <div class="h-scroll mt-5" data-drag-scroll>
        @foreach($achievementList as $ach)
        <div class="swipe-item">
            <div class="flex h-full w-44 flex-col items-center justify-center gap-2 rounded-2xl border border-black/5 bg-white p-4 text-center shadow-card md:w-52">
                <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-xl text-white shadow-md" style="background: {{ $ach['color'] }}">
                    @if($ach['image'])
                    <img src="{{ $ach['image'] }}" alt="{{ $ach['label'] }}" class="h-full w-full object-cover" loading="lazy">
                    @else
                    <i class="fas {{ $ach['icon'] }} text-xl"></i>
                    @endif
                </div>
                <div class="text-lg font-extrabold" style="color: {{ $ach['color'] }}">{{ $ach['display'] }}</div>
                <div class="text-xs leading-tight text-gray-500">{{ $ach['label'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

@if(isset($partnerLogos) && count($partnerLogos) > 0)
<section class="mt-14" data-reveal>
    <h2 class="text-xl font-bold text-primary-900">Logo Mitra</h2>
    <div class="h-scroll mt-5" data-drag-scroll>
        @foreach($partnerLogos as $logo)
        <div class="swipe-item grid h-28 w-44 place-items-center rounded-2xl border border-black/5 bg-white p-4 shadow-card md:w-48">
            <img src="{{ $logo }}" alt="Logo mitra" class="max-h-full max-w-full object-contain" loading="lazy">
        </div>
        @endforeach
    </div>
</section>
@endif

@if(isset($testimonials) && count($testimonials) > 0)
<section class="mt-14" data-reveal>
    <h2 class="text-xl font-bold text-primary-900">Testimoni Tokoh</h2>
    <div class="h-scroll mt-5" data-drag-scroll>
        @foreach($testimonials as $t)
        <div class="swipe-item w-80 md:w-96">
            <div class="flex h-full flex-col rounded-2xl border border-black/5 bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    @if($t['photo_url'])
                    <img src="{{ $t['photo_url'] }}" alt="{{ $t['name'] }}" class="h-12 w-12 rounded-full object-cover" loading="lazy">
                    @else
                    <div class="grid h-12 w-12 flex-shrink-0 place-items-center rounded-full bg-primary-100 text-lg font-bold text-primary-700">{{ mb_substr($t['name'], 0, 1) }}</div>
                    @endif
                    <p class="font-semibold text-primary-900">{{ $t['name'] }}</p>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-gray-600">&ldquo;{{ $t['text'] }}&rdquo;</p>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
