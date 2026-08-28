@push('scripts')
<script>
(function () {
    var slider = document.getElementById('testimonialSlider');
    if (!slider) return;

    var slides = slider.querySelectorAll('.testimonial-slide');
    if (slides.length < 2) return;

    var dotsWrap = document.getElementById('testimonialDots');
    var idx = 0;
    var timer = null;
    var interval = parseInt(slider.getAttribute('data-autoplay'), 10) || 6000;
    var dots = [];

    for (var i = 0; i < slides.length; i++) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', 'Testimoni ' + (i + 1));
        (function (k) {
            dot.addEventListener('click', function () { go(k); restart(); });
        })(i);
        dotsWrap.appendChild(dot);
        dots.push(dot);
    }

    function go(n) {
        slides[idx].classList.remove('active');
        if (dots[idx]) dots[idx].classList.remove('active');
        idx = (n + slides.length) % slides.length;
        slides[idx].classList.add('active');
        if (dots[idx]) dots[idx].classList.add('active');
    }

    function next() { go(idx + 1); }

    function restart() {
        clearInterval(timer);
        timer = setInterval(next, interval);
    }

    if (dots[0]) dots[0].classList.add('active');
    timer = setInterval(next, interval);

    slider.addEventListener('mouseenter', function () { clearInterval(timer); });
    slider.addEventListener('mouseleave', restart);
})();
</script>
@endpush
