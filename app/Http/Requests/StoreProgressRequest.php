<?php

namespace App\Http\Requests;

use App\Models\RepairProgress;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $assignment = $this->route('assignment');

        if (! $user || ! $assignment) {
            return false;
        }

        // Teknisi hanya boleh menambah progres pada penugasan miliknya
        return $user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'persentase' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_progres' => ['required', 'string', 'max:2000'],
            'waktu_progres' => ['nullable', 'date'],
        ];
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'persentase.required' => 'Persentase progres wajib diisi.',
            'persentase.integer' => 'Persentase progres harus berupa angka bulat.',
            'persentase.min' => 'Persentase progres minimal 0%.',
            'persentase.max' => 'Persentase progres maksimal 100%.',
            'catatan_progres.required' => 'Catatan progres wajib diisi.',
            'catatan_progres.max' => 'Catatan progres maksimal 2000 karakter.',
            'waktu_progres.date' => 'Waktu progres tidak valid.',
        ];
    }
}
