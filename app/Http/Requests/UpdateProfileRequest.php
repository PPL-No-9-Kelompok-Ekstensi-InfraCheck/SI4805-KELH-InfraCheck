<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna yang mengirim request boleh mengubah profilnya.
     *
     * Seluruh role memiliki hak akses dashboard.view, sehingga hak akses ini
     * dipakai sebagai penanda akun aktif tanpa menambah permission baru.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dashboard.view') ?? false;
    }

    /**
     * Aturan validasi untuk memperbarui profil pribadi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id_pengguna;

        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'id_pengguna'),
            ],
            'nim_nip' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nim_nip')->ignore($userId, 'id_pengguna'),
            ],
            'unit' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
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
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'nim_nip.max' => 'NIM/NIP maksimal 255 karakter.',
            'nim_nip.unique' => 'NIM/NIP sudah digunakan oleh pengguna lain.',
            'unit.max' => 'Unit maksimal 255 karakter.',
            'kontak.max' => 'Kontak maksimal 255 karakter.',
        ];
    }
}
