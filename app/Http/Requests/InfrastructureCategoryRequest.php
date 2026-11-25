<?php

namespace App\Http\Requests;

use App\Models\InfrastructureCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InfrastructureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-categories')
            && $this->user()->hasPermission($this->isMethod('post') ? 'categories.create' : 'categories.update');
    }

    public function rules(): array
    {
        $uniqueName = Rule::unique('infrastructure_categories', 'nama_kategori');
        $category = $this->route('category');

        if ($category instanceof InfrastructureCategory) {
            $uniqueName->ignore($category->getKey(), 'id_kategori');
        }

        return [
            'nama_kategori' => ['bail', 'required', 'string', 'max:255', $uniqueName],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.string' => 'Nama kategori harus berupa teks.',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ];
    }
}
