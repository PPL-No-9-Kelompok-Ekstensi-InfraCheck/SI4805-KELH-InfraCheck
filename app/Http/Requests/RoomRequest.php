<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-rooms');
    }

    public function rules(): array
    {
        return [
            'id_gedung' => ['bail', 'required', 'integer', Rule::exists('buildings', 'id_gedung')],
            'kode_ruangan' => ['required', 'string', 'max:255'],
            'nama_ruangan' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_gedung.required' => 'Gedung wajib dipilih.',
            'id_gedung.integer' => 'Pilihan gedung tidak valid.',
            'id_gedung.exists' => 'Gedung yang dipilih tidak tersedia.',
            'kode_ruangan.required' => 'Kode ruangan wajib diisi.',
            'kode_ruangan.string' => 'Kode ruangan harus berupa teks.',
            'kode_ruangan.max' => 'Kode ruangan maksimal 255 karakter.',
            'nama_ruangan.required' => 'Nama ruangan wajib diisi.',
            'nama_ruangan.string' => 'Nama ruangan harus berupa teks.',
            'nama_ruangan.max' => 'Nama ruangan maksimal 255 karakter.',
        ];
    }
}
