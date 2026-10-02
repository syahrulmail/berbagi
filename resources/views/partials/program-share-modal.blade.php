{{--
    Modal bagikan program untuk halaman Manajemen Program.
    URL share mengarah ke halaman CS agen yang sedang login.
--}}
@push('styles')
<style>
    #program-share-modal .share-program-name {
        margin-bottom: 14px;
        color: #08574f;
        font-size: 14px;
        font-weight: 600;
        word-break: break-word;
    }

    #program-share-modal .share-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    #program-share-modal .share-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 12px 6px;
        border: 1px solid #d2e2e0;
        border-radius: 12px;
        background: #fbfdfd;
        color: #08574f;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
    }

    #program-share-modal .share-item:hover {
        border-color: #086e66;
        background: #eefaf8;
    }

    #program-share-modal .share-item i {
        font-size: 20px;
    }

    #program-share-modal .share-item[data-share="whatsapp"] i { color: #25d366; }
    #program-share-modal .share-item[data-share="facebook"] i { color: #1877f2; }
    #program-share-modal .share-item[data-share="telegram"] i { color: #229ed9; }
    #program-share-modal .share-item[data-share="x"] i { color: #111; }
    #program-share-modal .share-item[data-share="linkedin"] i { color: #0a66c2; }
    #program-share-modal .share-item[data-share="instagram"] i { color: #e1306c; }
    #program-share-modal .share-item[data-share="tiktok"] i { color: #111; }

    #program-share-modal .share-copied {
        margin-top: 12px;
        color: #0f766e;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
    }

    @media (max-width: 560px) {
        #program-share-modal .share-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
</style>
@endpush

<div class="modal-backdrop" id="program-share-modal" role="dialog" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-share-nodes"></i> Bagikan Program</span>
            <button type="button" class="modal-close" data-program-share-close>&times;</button>
        </div>
        <div class="modal-body">
            <p class="share-program-name" data-program-share-name></p>
            <div class="share-grid">
                <a class="share-item" data-share="whatsapp" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i><span>WhatsApp</span></a>
                <a class="share-item" data-share="facebook" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i><span>Facebook</span></a>
                <a class="share-item" data-share="telegram" target="_blank" rel="noopener"><i class="fab fa-telegram"></i><span>Telegram</span></a>
                <a class="share-item" data-share="x" target="_blank" rel="noopener"><i class="fab fa-x-twitter"></i><span>X</span></a>
                <a class="share-item" data-share="linkedin" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i><span>LinkedIn</span></a>
                <a class="share-item" data-share="instagram" data-copy-only><i class="fab fa-instagram"></i><span>Instagram</span></a>
                <a class="share-item" data-share="tiktok" data-copy-only><i class="fab fa-tiktok"></i><span>TikTok</span></a>
                <button type="button" class="share-item" data-share="copy"><i class="fas fa-link"></i><span>Salin Link</span></button>
            </div>
            <p class="share-copied" data-share-copied hidden>Tautan berhasil disalin.</p>
        </div>
    </div>
</div>
