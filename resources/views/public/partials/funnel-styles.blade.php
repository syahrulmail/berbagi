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
</style>
@endpush
