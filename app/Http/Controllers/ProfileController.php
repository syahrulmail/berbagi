<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profiles)
    {
    }

    public function edit()
    {
        $user = auth()->user();
        $profile = $this->profiles->data($user);

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $this->profiles->save(auth()->user(), $request);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil disimpan.');
    }
}
