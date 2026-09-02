<div class="rounded-3xl border border-black/5 bg-white p-6 shadow-card donasi-cepat-card">
    <div class="flex items-center gap-2">
        <i class="fas fa-bolt text-gold-500"></i>
        <h2 class="text-sm font-bold uppercase tracking-wide text-primary-900">Donasi Cepat</h2>
    </div>

    <div class="mt-5">
        @if($showGoal)
        <div class="mb-1.5 flex items-baseline justify-between gap-2 text-sm">
            <span class="text-gray-500">Terkumpul</span>
            <strong class="text-primary-700">Rp {{ number_format($collected, 0, ',', '.') }}</strong>
        </div>
        <div class="progress-track" style="height:12px;">
            <div class="progress-fill" data-percent="{{ $progress }}" style="width:0;height:100%;"></div>
        </div>
        <div class="mt-1.5 flex items-center justify-between gap-2 text-xs text-gray-500">
            <span>Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
            <span class="font-semibold text-primary-700">{{ $progress }}%</span>
        </div>
        <div class="mt-3 rounded-xl bg-primary-50 px-4 py-3 text-xs text-gray-600">
            @if($isComplete)
                <span class="font-semibold text-emerald-600"><i class="fas fa-check-circle"></i> Target program tercapai. Terima kasih atas dukungan Anda.</span>
            @else
                Dibutuhkan <strong class="text-primary-700">Rp {{ number_format($remaining, 0, ',', '.') }}</strong> untuk mencapai target.
            @endif
        </div>
        @else
        <p class="rounded-xl bg-primary-50 px-4 py-3 text-xs text-gray-600">Salurkan wakaf, infak, dan sedekah Anda melalui program ini. Setiap kebaikan sangat berarti.</p>
        @endif
    </div>

    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-5 w-full !py-3.5"
       data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
        <i class="fab fa-whatsapp"></i> Berbagi sekarang
    </a>

    <div class="mt-4 flex items-center justify-center gap-2">
        <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-500 transition hover:bg-rose-100" data-program-like data-program-id="{{ $program->id }}" aria-label="Suka program ini">
            <i class="fas fa-heart"></i> <span data-program-like-count>{{ number_format($program->total_suka, 0, ',', '.') }}</span>
        </button>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500" title="Jumlah klik Detail">
            <i class="fas fa-arrow-pointer"></i> <span>{{ number_format($program->total_klik, 0, ',', '.') }}</span>
        </span>
    </div>

    <p class="mt-3 text-center text-xs text-gray-400">Mari berbagi, CS BWA siap melayani sepenuh hati</p>

    <div class="mt-5 flex gap-2">
        <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm flex-1"><i class="fab fa-whatsapp"></i> Bagikan</a>
        <button type="button" class="btn btn-outline btn-sm flex-1" data-copy-link><i class="fas fa-link"></i> Salin Link</button>
    </div>

    <div class="mt-4 flex items-center justify-center gap-3">
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke Facebook" title="Bagikan ke Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke X" title="Bagikan ke X"><i class="fab fa-x-twitter"></i></a>
        <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke Telegram" title="Bagikan ke Telegram"><i class="fab fa-telegram"></i></a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke LinkedIn" title="Bagikan ke LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="share-icon" data-share-copy="Instagram" aria-label="Bagikan ke Instagram" title="Bagikan ke Instagram"><i class="fab fa-instagram"></i></a>
        <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="share-icon" data-share-copy="TikTok" aria-label="Bagikan ke TikTok" title="Bagikan ke TikTok"><i class="fab fa-tiktok"></i></a>
        <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="share-icon" data-share-copy="Threads" aria-label="Bagikan ke Threads" title="Bagikan ke Threads"><i class="fab fa-threads"></i></a>
        <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="share-icon" data-share-copy="Discord" aria-label="Bagikan ke Discord" title="Bagikan ke Discord"><i class="fab fa-discord"></i></a>
    </div>

    <div class="mt-5 border-t border-black/5 pt-4 text-xs text-gray-500">
        <p>Butuh bantuan? Hubungi</p>
        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 font-semibold text-primary-600 hover:text-primary-700"
           data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
            <i class="fab fa-whatsapp"></i> {{ $waNumber }}
        </a>
    </div>
</div>
