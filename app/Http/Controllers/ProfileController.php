<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan profil milik pengguna yang sedang masuk.
     */
    public function index(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user()->loadMissing('role'),
        ]);
    }

    /**
     * Memperbarui data profil milik pengguna yang sedang masuk.
     *
     * Hanya kolom data diri yang boleh berubah. Role dan status akun sengaja
     * tidak pernah diambil dari request agar tidak terjadi eskalasi hak akses.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'nim_nip' => $data['nim_nip'] ?? null,
            'unit' => $data['unit'] ?? null,
            'kontak' => $data['kontak'] ?? null,
        ]);

        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Mengubah password milik pengguna setelah password lama diverifikasi.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Cast "hashed" pada model akan melakukan hash otomatis.
        $user->kata_sandi_hash = $data['password'];
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('status', 'Password berhasil diubah.');
    }
}
