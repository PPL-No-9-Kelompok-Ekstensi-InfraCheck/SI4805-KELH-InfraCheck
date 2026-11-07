<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna yang mengirim request boleh melakukan aksi ini.
     * Pengelolaan data pengguna hanya untuk Admin.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->akun_aktif
            && $user->role !== null
            && strcasecmp($user->role->nama_peran, 'Admin') === 0;
    }

    /**
     * Aturan validasi untuk memperbarui data pengguna.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id_pengguna, 'id_pengguna'),
            ],
            'nim_nip' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nim_nip')->ignore($user?->id_pengguna, 'id_pengguna'),
            ],
            'id_peran' => ['required', 'integer', Rule::exists('roles', 'id_peran')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'unit' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'akun_aktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pengguna wajib diisi.',
            'nama.max' => 'Nama pengguna maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'nim_nip.unique' => 'NIM/NIP sudah digunakan oleh pengguna lain.',
            'id_peran.required' => 'Role wajib dipilih.',
            'id_peran.exists' => 'Role yang dipilih tidak tersedia.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
