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
        .hero-mesh::before, .hero-shimmer-text, .hero-blob-a, .hero-blob-b, .hero-blob-c {
            animation: none;
        }
        .hero-enter {
            opacity: 1;
            transform: none;
            animation: none;
        }
    }
</style>
@endpush
