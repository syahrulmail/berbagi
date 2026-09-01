@php
    $defaultTag = null;
    $otherTags = [];
    foreach ($p['tags'] ?? [] as $t) {
        if (!empty($t['is_default']) && !$defaultTag) {
            $defaultTag = $t;
        } elseif (empty($t['is_default'])) {
            $otherTags[] = $t;
        }
    }
    $tagTextColor = function ($hex) {
        $h = ltrim((string) $hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        $n = hexdec($h);
        $r = ($n >> 16) & 255;
        $g = ($n >> 8) & 255;
        $b = $n & 255;
        return ((0.299 * $r + 0.587 * $g + 0.114 * $b) / 255) > 0.6 ? '#0b2f2d' : '#ffffff';
    };
@endphp
<article class="program-card group flex flex-col overflow-hidden rounded-3xl border border-black/5 bg-white shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ $p['url'] }}" class="relative block aspect-[4/3] overflow-hidden">
        @if($p['image'])
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="grid h-full w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400"><i class="fas fa-book-quran" style="font-size:40px;"></i></div>
        @endif
        <span class="absolute left-3 top-3 flex items-center gap-1.5">
            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide text-white {{ $p['category'] === 'penyaluran' ? 'bg-gold-500' : 'bg-primary-500' }}">{{ $p['category'] }}</span>
            @if($defaultTag)
            <span class="rounded-full px-3 py-1 text-xs font-bold text-white" style="background: {{ $defaultTag['color'] }}; color: {{ $tagTextColor($defaultTag['color']) }};">{{ $defaultTag['name'] }}</span>
            @endif
        </span>
        @if(!empty($p['show_goal']) && $p['is_complete'])
            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold text-white"><i class="fas fa-check-circle"></i> Tercapai</span>
        @elseif(!empty($p['show_goal']) && $p['progress'] >= 90)
            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-gold-500 px-3 py-1 text-xs font-bold text-white"><i class="fas fa-fire"></i> Hampir Tercapai</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        @if(count($otherTags))
        <div class="mb-2 flex flex-wrap gap-1.5">
            @foreach($otherTags as $t)
            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" style="background: {{ $t['color'] }}; color: {{ $tagTextColor($t['color']) }};">{{ $t['name'] }}</span>
            @endforeach
        </div>
        @endif
        <h3 class="mb-1.5 text-lg font-bold text-primary-900"><a href="{{ $p['url'] }}" class="transition-colors hover:text-primary-600">{{ $p['name'] }}</a></h3>
        <div class="mt-auto">
            @if(!empty($p['show_goal']))
            <div class="mb-1.5 flex items-center justify-between gap-2 text-xs text-gray-500">
                <span>Terkumpul <strong class="text-primary-700">{{ $p['collected'] }}</strong></span>
                <span>Target {{ $p['goal'] }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-primary-100">
                <div class="h-full rounded-full bg-gradient-to-r from-primary-500 to-emerald-400" style="width: {{ max(4, $p['progress']) }}%"></div>
            </div>
            @endif
            <div class="mt-4 flex gap-2">
                <a href="{{ $p['wa_url'] }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm flex-1"
                   data-wa-log="1" data-wa-source="{{ $p['wa_source'] }}" data-wa-program="{{ $p['wa_program'] }}"><i class="fab fa-whatsapp"></i> Berbagi sekarang</a>
                <a href="{{ $p['url'] }}" class="btn btn-outline btn-sm"><i class="fas fa-circle-info"></i> Detail</a>
            </div>
            @if(!empty($p['edit_url']))
            <a href="{{ $p['edit_url'] }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 transition-colors hover:text-primary-600"><i class="fas fa-pen-to-square"></i> Edit program</a>
            @endif
        </div>
    </div>
</article>
