@if(trim(strip_tags($homeQuote)) !== '')
<section class="border-b border-black/5 bg-white py-16" data-reveal>
    <div class="container mx-auto max-w-3xl text-center">
        <i class="fas fa-quote-left mb-4 text-3xl text-gold-400"></i>
        <div class="quote-content text-xl font-light leading-relaxed text-primary-900 md:text-2xl">{!! $homeQuote !!}</div>
    </div>
</section>
@endif

@if(count($testimonials) > 0)
<section class="border-b border-black/5 bg-primary-50/40 py-16" data-reveal>
    <div class="container mx-auto max-w-3xl">
        <div id="testimonialSlider" class="relative" data-autoplay="6000">
            @foreach($testimonials as $i => $testimonial)
            <div class="testimonial-slide {{ $i === 0 ? 'active' : '' }}">
                <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-10">
                    @if($testimonial['photo_url'])
                    <img src="{{ $testimonial['photo_url'] }}" alt="{{ $testimonial['name'] }}" class="testimonial-photo" loading="lazy">
                    @else
                    <div class="testimonial-photo-placeholder">{{ mb_substr($testimonial['name'], 0, 1) }}</div>
                    @endif
                    <div class="text-center sm:text-left">
                        <p class="testimonial-text">&ldquo;{{ $testimonial['text'] }}&rdquo;</p>
                        <p class="testimonial-name mt-4">{{ $testimonial['name'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if(count($testimonials) > 1)
        <div class="mt-8 flex justify-center gap-2.5" id="testimonialDots"></div>
        @endif
    </div>
</section>
@endif

@if(count($partnerLogos) > 0)
<section class="border-b border-black/5 bg-white py-14" data-reveal>
    <div class="container mx-auto max-w-6xl">
        <div class="logo-marquee">
            <div class="logo-track">
                @foreach($partnerLogos as $logo)
                <img src="{{ $logo }}" alt="Logo mitra" class="logo-item" loading="lazy">
                @endforeach
                @foreach($partnerLogos as $logo)
                <img src="{{ $logo }}" alt="Logo mitra" class="logo-item" loading="lazy" aria-hidden="true">
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
