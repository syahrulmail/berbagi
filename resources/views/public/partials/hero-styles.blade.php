@push('styles')
<style>
    .hero-mesh {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }
    .hero-mesh::before {
        content: '';
        position: absolute;
        left: -10%;
        right: -10%;
        top: -40%;
        bottom: -40%;
        background-image:
            linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
        background-size: 64px 64px;
        transform: perspective(600px) rotateX(55deg) translateY(18%);
        transform-origin: center top;
        animation: meshDrift 22s linear infinite;
    }
    @keyframes meshDrift {
        from { background-position: 0 0, 0 0; }
        to { background-position: 0 64px, 64px 0; }
    }
    .hero-blob-a,
    .hero-blob-b,
    .hero-blob-c {
        position: absolute;
        border-radius: 9999px;
        filter: blur(3rem);
        pointer-events: none;
    }
    .hero-blob-a {
        width: 26rem;
        height: 26rem;
        right: -8rem;
        top: -8rem;
        background: rgba(255, 196, 87, .22);
        animation: blobFloat 12s ease-in-out infinite;
    }
    .hero-blob-b {
        width: 22rem;
        height: 22rem;
        left: -10rem;
        bottom: -10rem;
        background: rgba(212, 145, 30, .18);
        animation: blobFloat 14s ease-in-out infinite reverse;
    }
    .hero-blob-c {
        width: 14rem;
        height: 14rem;
        left: 42%;
        bottom: 12%;
        background: rgba(255, 255, 255, .08);
        animation: blobFloat 18s ease-in-out infinite;
    }
    @keyframes blobFloat {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-26px) scale(1.06); }
    }

    .hero-shimmer-text {
        display: inline-block;
        background: linear-gradient(100deg, #f6c86a 20%, #fff3d0 40%, #ffd98a 50%, #fff3d0 60%, #f6c86a 80%);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        animation: shimmerText 3.2s linear infinite;
    }
    @keyframes shimmerText {
        0% { background-position: 0% 0; }
        100% { background-position: -200% 0; }
    }

    .hero-cutout-shell {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 520px;
    }
    .hero-cutout-glow {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 460px;
        height: 460px;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(255, 200, 90, .28) 0%, rgba(8, 142, 132, .18) 45%, transparent 70%);
        filter: blur(18px);
        border-radius: 9999px;
        pointer-events: none;
        animation: cutoutGlow 6s ease-in-out infinite;
    }
    @keyframes cutoutGlow {
        0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: .9; }
        50% { transform: translate(-50%, -50%) scale(1.12); opacity: 1; }
    }
    .hero-cutout-stage {
        position: relative;
        width: 100%;
        height: 460px;
        display: grid;
        place-items: center;
        transform-style: preserve-3d;
        will-change: transform;
        transition: transform .25s ease-out;
    }
    .hero-cutout-slide {
        grid-area: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
        width: 100%;
        height: 100%;
        opacity: 0;
        transform: scale(1.08);
        transition: opacity .5s ease, transform .7s cubic-bezier(.22, .61, .36, 1);
        pointer-events: none;
    }
    .hero-cutout-slide.active {
        opacity: 1;
        transform: scale(1);
        pointer-events: auto;
    }
    .hero-cutout-imgwrap {
        display: block;
        line-height: 0;
        animation: cutoutFloat 6s ease-in-out infinite;
        transform: translateZ(30px);
    }
    @keyframes cutoutFloat {
        0%, 100% { transform: translateZ(30px) translateY(0); }
        50% { transform: translateZ(30px) translateY(-14px); }
    }
    .hero-cutout-img {
        display: block;
        width: auto;
        max-width: 460px;
        max-height: 460px;
        filter: drop-shadow(0 24px 40px rgba(2, 35, 33, .35));
        object-fit: contain;
    }
    @media (max-width: 640px) {
        .hero-cutout-shell { min-height: 420px; }
        .hero-cutout-stage { height: 360px; }
        .hero-cutout-img { max-width: 340px; max-height: 340px; }
        .hero-cutout-glow { width: 340px; height: 340px; }
    }

    .hero-enter {
        opacity: 0;
        transform: translateY(18px);
        animation: heroFadeUp .7s cubic-bezier(.22, .61, .36, 1) forwards;
    }
    .hero-enter-1 { animation-delay: .05s; }
    .hero-enter-2 { animation-delay: .18s; }
    .hero-enter-3 { animation-delay: .32s; }
    .hero-enter-4 { animation-delay: .46s; }
    .hero-enter-5 { animation-delay: .6s; }
    @keyframes heroFadeUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hero-mesh::before, .hero-shimmer-text, .hero-blob-a, .hero-blob-b, .hero-blob-c,
        .hero-cutout-glow, .hero-cutout-imgwrap, .hero-cutout-slide {
            animation: none;
        }
        .hero-cutout-slide {
            opacity: 1;
            transform: none;
            transition: none;
        }
        .hero-cutout-slide:not(.active) { display: none; }
        .hero-enter {
            opacity: 1;
            transform: none;
            animation: none;
        }
    }
</style>
@endpush
