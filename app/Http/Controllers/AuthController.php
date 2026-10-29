<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman formulir login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses autentikasi pengguna menggunakan Email atau NIM/NIP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email atau NIM / NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($request->input('login'));
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'nim_nip';

        $remember = $request->boolean('remember');

        if (Auth::attempt([$fieldType => $loginInput, 'password' => $request->password], $remember)) {
            // Periksa apakah akun aktif
            if (!Auth::user()->akun_aktif) {
                Auth::logout();
                return back()->withErrors([
                    'login' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi Unit IT.',
                ])->onlyInput('login');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'Email/NIM/NIP atau password yang dimasukkan salah.',
        ])->onlyInput('login');
    }

    /**
     * Mengeluarkan pengguna dari sistem.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
