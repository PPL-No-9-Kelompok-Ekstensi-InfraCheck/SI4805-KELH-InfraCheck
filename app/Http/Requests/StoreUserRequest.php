<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\RestrictsAssignableRoles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use RestrictsAssignableRoles;

    /**
     * Menentukan apakah pengguna yang mengirim request boleh melakukan aksi ini.
     * Pembuatan pengguna hanya untuk pemegang hak akses Tambah Pengguna.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') ?? false;
    }

    /**
     * Aturan validasi untuk membuat pengguna baru.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'nim_nip' => ['nullable', 'string', 'max:255', Rule::unique('users', 'nim_nip')],
            'id_peran' => $this->roleAssignmentRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'id_peran.not_in' => 'Anda tidak dapat menetapkan role Admin.',
            'id_peran.in' => 'Role yang dipilih memiliki hak akses lebih luas daripada hak akses Anda.',
            'password.required' => 'Password wajib diisi saat membuat pengguna baru.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
