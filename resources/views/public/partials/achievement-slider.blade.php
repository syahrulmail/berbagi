@push('styles')
<style>
    .ach-value {
        font-size: 30px;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: .2px;
    }
    .ach-icon {
        font-size: 28px;
    }
    .ach-enter-active,
    .ach-leave-active {
        transition: opacity .45s ease, transform .45s ease;
    }
    .ach-enter-from {
        opacity: 0;
        transform: translateY(12px);
    }
    .ach-leave-to {
        opacity: 0;
        transform: translateY(-12px);
    }
    @media (min-width: 768px) {
        .ach-value {
            font-size: 38px;
        }
    }
</style>
@endpush

@php
    $achievementSlides = $achievements->map(function ($a) {
        return [
            'icon'  => $a->icon,
            'image' => $a->image_url,
            'color' => $a->color ?: '#08A899',
            'value' => $a->value,
            'label' => $a->label,
        ];
    })->values();
@endphp

@if($achievementSlides->isNotEmpty())
<section class="border-b border-black/5 bg-primary-50/40 py-10" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-2xl">
            <div data-vue-app="AchievementSlider">
                <script type="application/json">@json(['items' => $achievementSlides])</script>
            </div>
        </div>
    </div>
</section>
@endif
