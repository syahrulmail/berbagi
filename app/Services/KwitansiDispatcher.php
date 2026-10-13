<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\User;

/**
 * Mengirim kwitansi donasi ke WhatsApp donatur: gambar kwitansi sebagai media
 * ditambah caption berisi rincian + tautan kwitansi publik.
 */
class KwitansiDispatcher
{
    /** @var FollowupWaService */
    protected $followup;

    /** @var WhatsAppSenderService */
    protected $sender;

    /** @var KwitansiImageService */
    protected $images;

    public function __construct(
        FollowupWaService $followup,
        WhatsAppSenderService $sender,
        KwitansiImageService $images
    ) {
        $this->followup = $followup;
        $this->sender = $sender;
        $this->images = $images;
    }

    /**
     * @return array{sent:bool,reason:?string,provider?:?string,link:string,image_url:?string,wa_url:string}
     */
    public function send(User $user, Donation $donation): array
    {
        $donation->load(['contact', 'items.program', 'program']);

        $link = kwitansi_public_url($donation);
        $caption = kwitansi_caption($donation, $link);
        $waFallback = kwitansi_link_wa_url($donation, $link);

        $contact = $donation->contact;
        $phone = $contact ? trim((string) $contact->phone) : '';
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return [
                'sent' => false,
                'reason' => 'Nomor WhatsApp donatur belum tersedia.',
                'link' => $link,
                'image_url' => null,
                'wa_url' => '',
            ];
        }

        $imagePath = $this->images->ensure($donation);
        $imageUrl = $imagePath ? asset_photo_url($imagePath) : '';

        $provider = $this->followup->resolveProvider($user, false);
        if (empty($provider['ok'])) {
            return [
                'sent' => false,
                'reason' => $provider['reason'] ?: 'API WhatsApp belum siap.',
                'link' => $link,
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'wa_url' => $waFallback,
            ];
        }

        $result = $this->sender->send(
            $provider['provider'],
            $provider['key'],
            $phone,
            $caption,
            [
                'media_url' => $imageUrl,
                'media_type' => 'image',
            ]
        );

        if (empty($result['ok'])) {
            return [
                'sent' => false,
                'reason' => $result['error'] ?: 'Gagal mengirim pesan WhatsApp.',
                'link' => $link,
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'wa_url' => $waFallback,
            ];
        }

        return [
            'sent' => true,
            'reason' => null,
            'provider' => $result['provider'],
            'link' => $link,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'wa_url' => $waFallback,
        ];
    }
}
