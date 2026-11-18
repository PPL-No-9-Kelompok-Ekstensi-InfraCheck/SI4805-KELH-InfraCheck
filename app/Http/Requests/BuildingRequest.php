<?php

namespace App\Http\Requests;

use App\Models\Building;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-buildings');
    }

    public function rules(): array
    {
        $uniqueCode = Rule::unique('buildings', 'kode_gedung');
        $building = $this->route('building');

        if ($building instanceof Building) {
            $uniqueCode->ignore($building->getKey(), 'id_gedung');
        }

        return [
            'kode_gedung' => ['required', 'string', 'max:255', $uniqueCode],
            'nama_gedung' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_gedung.required' => 'Kode gedung wajib diisi.',
            'kode_gedung.string' => 'Kode gedung harus berupa teks.',
            'kode_gedung.max' => 'Kode gedung maksimal 255 karakter.',
            'kode_gedung.unique' => 'Kode gedung sudah digunakan.',
            'nama_gedung.required' => 'Nama gedung wajib diisi.',
            'nama_gedung.string' => 'Nama gedung harus berupa teks.',
            'nama_gedung.max' => 'Nama gedung maksimal 255 karakter.',
        ];
    }
}
