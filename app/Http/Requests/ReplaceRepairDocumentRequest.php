<?php

namespace App\Http\Requests;

use App\Models\RepairDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Mengganti berkas dokumentasi yang salah (PBI 18).
 */
class ReplaceRepairDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof RepairDocument
            && Gate::forUser($this->user())->allows('update', $document);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'berkas' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih foto pengganti terlebih dahulu.',
            'berkas.file' => 'Foto pengganti gagal diunggah.',
            'berkas.mimes' => 'Foto pengganti harus berformat JPG, JPEG, atau PNG.',
            'berkas.max' => 'Ukuran foto pengganti maksimal 2 MB.',
        ];
    }
}
