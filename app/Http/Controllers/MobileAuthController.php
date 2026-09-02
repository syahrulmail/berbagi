<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    /**
     * Tampilkan halaman login mobile (/mo/login).
     */
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('mo.dashboard');
        }

        return view('mobile.login');
    }

    /**
     * Proses login mobile, lalu kembali ke dashboard mobile (/mo/dashboard).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $credentials['login'])->where('is_active', true)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Kredensial yang Anda masukkan salah.',
            ])->withInput();
        }

        if ($user->isDonatur()) {
            return back()->withErrors([
                'login' => 'Akun donatur tidak memiliki akses login. Silakan hubungi admin bila ini sebuah kesalahan.',
            ])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));

        ActivityLog::record('login', $user->name . ' login ke sistem');

        return redirect()->intended(route('mo.dashboard'));
    }
}
