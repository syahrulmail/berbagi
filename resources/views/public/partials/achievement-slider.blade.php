@push('styles')
<style>
    .ach-value {
        font-size: 16px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: .2px;
    }
    .ach-icon {
        font-size: 20px;
    }
    .ach-label {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.3;
    }
    .achievement-card {
        min-width: 0;
        padding: 10px 12px;
    }
    @media (min-width: 768px) {
        .ach-value {
            font-size: 20px;
        }
        .ach-label {
            font-size: 13px;
        }
    }
</style>
@endpush

@php
    $achievementSlides = $achievements->map(function ($a) {
        $parts = $a->numericParts();
        return [
            'icon'     => $a->icon,
            'image'    => $a->image_url,
            'color'    => $a->color ?: '#08A899',
            'value'    => $a->value,
            'label'    => $a->label,
            'number'   => $parts['number'],
            'prefix'   => $parts['prefix'],
            'decimals' => $parts['decimals'],
            'suffix'   => $parts['suffix'],
        ];
    })->values();
@endphp

@if($achievementSlides->isNotEmpty())
<section class="border-b border-black/5 bg-primary-50/40 py-14" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-5xl">
            <div data-vue-app="AchievementSlider">
                <script type="application/json">@json(['items' => $achievementSlides])</script>
            </div>
        </div>
    </div>
</section>
@endif
