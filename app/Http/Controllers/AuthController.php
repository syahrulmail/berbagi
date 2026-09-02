<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

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

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        ActivityLog::record('logout', $user ? $user->name . ' logout dari sistem' : 'logout dari sistem');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
