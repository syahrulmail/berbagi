@push('styles')
<style>
    .quote-content blockquote {
        margin: 0.5rem 0;
        padding: 0.25rem 0 0.25rem 1rem;
        border-left: 3px solid #d4911e;
        text-align: left;
        color: #086e66;
        font-style: italic;
    }
    .testimonial-photo {
        width: 120px;
        height: 120px;
        object-fit: contain;
        flex: none;
        filter: drop-shadow(0 8px 16px rgba(8, 110, 102, 0.18));
    }
    .testimonial-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 120px;
        height: 120px;
        flex: none;
        border-radius: 9999px;
        background: #e7f4f2;
        color: #086e66;
        font-size: 44px;
        font-weight: 700;
    }
    .testimonial-slide { display: none; }
    .testimonial-slide.active { display: block; animation: tFade .6s ease; }
    @keyframes tFade {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: none; }
    }
    .testimonial-text {
        font-style: italic;
        font-weight: 400;
        font-size: 1.15rem;
        line-height: 1.8;
        color: #064e3b;
    }
    .testimonial-name {
        font-style: normal;
        font-weight: 700;
        color: #0f172a;
    }
    #testimonialDots button {
        width: 9px;
        height: 9px;
        border-radius: 9999px;
        border: none;
        padding: 0;
        cursor: pointer;
        background: #cbe4e0;
        transition: background .2s, transform .2s;
    }
    #testimonialDots button.active {
        background: #08A899;
        transform: scale(1.25);
    }
    .logo-marquee { overflow: hidden; }
    .logo-track {
        display: flex;
        align-items: center;
        gap: 56px;
        width: max-content;
        animation: logoScroll 40s linear infinite;
    }
    .logo-marquee:hover .logo-track { animation-play-state: paused; }
    .logo-item {
        height: 60px;
        width: auto;
        object-fit: contain;
        flex: none;
        opacity: .8;
        transition: opacity .2s;
    }
    .logo-item:hover { opacity: 1; }
    @keyframes logoScroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }
    .filter-bar-sticky {
        position: sticky;
        top: 76px;
        z-index: 30;
        align-items: center;
        margin-bottom: 20px;
        padding: 12px 16px;
        border: 1px solid rgba(0, 0, 0, .06);
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 24px -12px rgba(2, 35, 33, .18);
    }
    .filter-bar-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;
    }
    .filter-bar-row .search-box {
        min-width: 0;
        flex: 1;
    }
    .filter-bar-row .filter-toggle {
        flex: none;
    }
    .filter-toggle {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        align-self: center;
        padding: 10px 14px;
        border: 1px solid rgba(0, 0, 0, .1);
        border-radius: 12px;
        background: #fff;
        color: #4b5563;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
    }
    .filter-toggle:hover {
        border-color: rgba(8, 168, 153, .4);
        color: #086e66;
    }
    .filter-toggle.active {
        border-color: #08A899;
        background: #e7f4f2;
        color: #086e66;
    }
    .filter-toggle i.fa-chevron-up,
    .filter-toggle i.fa-chevron-down {
        font-size: 11px;
        margin-left: 2px;
    }
    .filter-fade-enter-active,
    .filter-fade-leave-active {
        transition: opacity .22s ease, transform .22s ease;
        transform-origin: top center;
    }
    .filter-fade-enter-from,
    .filter-fade-leave-to {
        opacity: 0;
        transform: translateY(-6px);
    }
    .popular-badge {
        position: absolute;
        left: 12px;
        bottom: 12px;
        z-index: 3;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        padding: 5px 12px;
        border-radius: 9999px;
        box-shadow: 0 6px 16px -4px rgba(217, 119, 6, .55);
        animation: badgePulse 2.2s ease-in-out infinite;
    }
    .popular-badge i {
        animation: badgeFlame 1.1s ease-in-out infinite;
    }
    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 6px 16px -4px rgba(217, 119, 6, .55); }
        50% { box-shadow: 0 6px 22px 0 rgba(245, 158, 11, .75); }
    }
    @keyframes badgeFlame {
        0%, 100% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(-12deg) scale(1.18); }
    }
    .card-shine {
        position: relative;
    }
    .card-shine::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        background: linear-gradient(115deg, transparent 30%, rgba(255, 255, 255, .45) 48%, transparent 62%);
        transform: translateX(-120%);
        transition: transform .9s cubic-bezier(.22, .61, .36, 1);
    }
    .card-shine:hover::after {
        transform: translateX(120%);
    }
    .program-progress-anim {
        position: relative;
        overflow: hidden;
        animation: progressFill 1.4s cubic-bezier(.22, .61, .36, 1);
    }
    @keyframes progressFill {
        from { width: 0; }
        to { width: var(--p, 0); }
    }
    .program-progress-anim::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, transparent 30%, rgba(255, 255, 255, .5) 50%, transparent 70%);
        background-size: 200% 100%;
        animation: progressShimmer 2.4s linear infinite;
    }
    @keyframes progressShimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .pdetail-overlay {
        position: fixed;
        inset: 0;
        z-index: 90;
        background: rgba(2, 44, 41, .58);
        backdrop-filter: blur(3px);
        display: flex;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 20px 16px;
    }
    .pdetail-modal {
        margin: auto;
        width: 100%;
        max-width: 520px;
        max-height: calc(100vh - 40px);
        background: #fff;
        border-radius: 22px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 28px 70px rgba(2, 44, 41, .4);
    }
    .pdetail-media {
        position: relative;
        height: 210px;
        flex: none;
        background: linear-gradient(135deg, #08574f, #022321);
    }
    @media (max-width: 480px) {
        .pdetail-media { height: 160px; }
        .pdetail-body { padding: 16px 16px 18px; }
        .pdetail-title { font-size: 17px; }
        .pdetail-desc { font-size: 12.5px; }
    }
    .pdetail-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pdetail-media-ph {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: rgba(255, 255, 255, .35);
        font-size: 56px;
    }
    .pdetail-close {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 10px;
        background: rgba(2, 35, 33, .45);
        color: #fff;
        cursor: pointer;
        font-size: 16px;
        display: grid;
        place-items: center;
    }
    .pdetail-close:hover { background: rgba(2, 35, 33, .7); }
    .pdetail-cat {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 2;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #fff;
        background: #08A899;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-cat.is-gold { background: #D4911E; }
    .pdetail-done {
        position: absolute;
        right: 56px;
        top: 15px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #10b981;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-hot {
        position: absolute;
        right: 56px;
        top: 15px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #D4911E;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-body {
        padding: 20px 22px 22px;
        overflow-y: auto;
    }
    .pdetail-title { font-size: 20px; font-weight: 800; color: #04211f; line-height: 1.3; }
    .pdetail-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .pdetail-tag {
        font-size: 11px;
        font-weight: 600;
        color: #086e66;
        background: #e7f4f2;
        padding: 3px 10px;
        border-radius: 9999px;
    }
    .pdetail-tag.is-gold { background: #f9edcd; color: #935516; }
    .pdetail-desc { font-size: 13.5px; line-height: 1.7; color: #4b5563; margin-top: 12px; }
    .pdetail-progress { margin-top: 16px; }
    .pdetail-progress-track {
        height: 10px;
        border-radius: 9999px;
        background: #e7f4f2;
        overflow: hidden;
    }
    .pdetail-progress-track div {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #08A899, #34d399);
    }
    .pdetail-progress-meta {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        margin-top: 7px;
        font-size: 12px;
    }
    .pdetail-pct { font-weight: 800; color: #086e66; font-size: 14px; }
    .pdetail-rem { color: var(--gray-500); font-weight: 600; }
    .pdetail-rem strong { color: #086e66; }
    .pdetail-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 14px;
    }
    .pdetail-stats div {
        background: #f4f8f7;
        border-radius: 12px;
        padding: 10px 12px;
        text-align: center;
    }
    .pdetail-stats span {
        display: block;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--gray-500);
        margin-bottom: 3px;
    }
    .pdetail-stats strong { font-size: 14px; font-weight: 800; color: #043d3a; }
    .pdetail-stats strong.is-gold { color: #935516; }
    .pdetail-trust {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 14px;
        font-size: 11.5px;
        font-weight: 600;
        color: #086e66;
    }
    .pdetail-trust i { color: #08A899; margin-right: 4px; }
    .pdetail-cta {
        margin-top: 16px;
        padding: 14px 18px;
        font-size: 15px;
        border-radius: 14px;
    }
    .pdetail-more {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 14px;
        font-size: 13px;
        font-weight: 600;
        color: #086e66;
        text-align: center;
    }
    .pdetail-more:hover { color: #08A899; }
    .pdetail-more-arrow { transition: transform .2s; }
    .pdetail-more:hover .pdetail-more-arrow { transform: translateX(3px); }
    .pdetail-overlay,
    .pdetail-body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .pdetail-overlay::-webkit-scrollbar,
    .pdetail-body::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }
    @media (max-width: 520px) {
        .pdetail-stats { grid-template-columns: repeat(2, 1fr); }
        .pdetail-stats div:last-child { grid-column: span 2; }
    }
    .cta-quote {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
        margin-top: 20px;
        white-space: nowrap;
        font-size: clamp(9.2px, 2.35vw, 18px);
        line-height: 1.4;
        font-style: italic;
        color: rgba(255,255,255,0.95);
    }
    .cta-box { padding: 2.5rem 0.75rem; }
    @media (min-width: 640px) { .cta-box { padding: 2.5rem; } }
</style>
@endpush
