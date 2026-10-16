<?php

namespace App\Http\Controllers;

use App\Services\FollowupWaService;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $profiles;

    protected $followup;

    public function __construct(ProfileService $profiles, FollowupWaService $followup)
    {
        $this->profiles = $profiles;
        $this->followup = $followup;
    }

    public function edit()
    {
        $user = auth()->user();
        $profile = $this->profiles->data($user);
        $webhookUrlSs = $this->followup->webhookUrl($user, 'starsender');
        $webhookUrlCc = $this->followup->webhookUrl($user, 'cloudchat');

        return view('profile.edit', compact('user', 'profile', 'webhookUrlSs', 'webhookUrlCc'));
    }

    public function update(Request $request)
    {
        $this->profiles->save(auth()->user(), $request);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil disimpan.');
    }
}
