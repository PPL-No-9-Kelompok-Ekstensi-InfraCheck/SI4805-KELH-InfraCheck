<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $assignment = $this->route('assignment');
        if ($assignment) {
            return $user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna;
        }

        return $user->hasRole('Teknisi') || $user->hasRole('Admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_penugasan' => ['sometimes', 'required', 'exists:assignments,id_penugasan'],
            'waktu_mulai' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'catatan_jadwal' => ['nullable', 'string', 'max:1000'],
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
            'id_penugasan.required' => 'Pilih penugasan yang akan dijadwalkan.',
            'id_penugasan.exists' => 'Penugasan yang dipilih tidak valid.',
            'waktu_mulai.required' => 'Waktu mulai perbaikan wajib diisi.',
            'waktu_mulai.date' => 'Format waktu mulai tidak valid.',
            'target_selesai.required' => 'Target selesai perbaikan wajib diisi.',
            'target_selesai.date' => 'Format target selesai tidak valid.',
            'target_selesai.after_or_equal' => 'Target selesai harus sama dengan atau setelah waktu mulai.',
            'catatan_jadwal.max' => 'Catatan jadwal maksimal 1000 karakter.',
        ];
    }
}
