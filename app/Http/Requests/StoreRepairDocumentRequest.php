<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use App\Models\RepairDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Unggah dokumentasi hasil perbaikan (PBI 18).
 */
class StoreRepairDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment instanceof Assignment
            && Gate::forUser($this->user())->allows('upload', [RepairDocument::class, $assignment]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $aturanFoto = ['nullable', 'array', 'max:10'];
        $aturanBerkas = ['file', 'mimes:jpg,jpeg,png', 'max:2048'];

        return [
            'tindakan' => ['nullable', 'required_if:tandai_selesai,1', 'string', 'max:1200'],
            'suku_cadang' => ['nullable', 'string', 'max:600'],
            'kondisi_perangkat' => ['nullable', 'string', 'in:Baik,Rusak Ringan,Rusak Berat'],
            'tandai_selesai' => ['nullable', 'boolean'],
            'foto_sebelum' => $aturanFoto,
            'foto_sebelum.*' => $aturanBerkas,
            'foto_selama' => $aturanFoto,
            'foto_selama.*' => $aturanBerkas,
            'foto_sesudah' => $aturanFoto,
            'foto_sesudah.*' => $aturanBerkas,
        ];
    }

    /**
     * Foto Sebelum dan Sesudah wajib ada ketika perbaikan ditandai selesai
     * (termasuk foto yang sudah diunggah sebelumnya).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $baru = [];
            foreach (RepairDocument::JENIS as $jenis => $field) {
                $baru[$jenis] = count((array) $this->file($field));
            }

            if (array_sum($baru) === 0) {
                $validator->errors()->add('foto', 'Unggah minimal satu foto dokumentasi.');

                return;
            }

            if (! $this->boolean('tandai_selesai')) {
                return;
            }

            $assignment = $this->route('assignment');

            foreach (['Sebelum', 'Sesudah'] as $jenis) {
                $sudahAda = RepairDocument::query()
                    ->where('jenis_dokumentasi', $jenis)
                    ->whereHas('progress', fn ($q) => $q->where('id_penugasan', $assignment->id_penugasan))
                    ->exists();

                if ($baru[$jenis] === 0 && ! $sudahAda) {
                    $validator->errors()->add(
                        RepairDocument::JENIS[$jenis],
                        "Foto {$jenis} perbaikan wajib dilampirkan untuk menandai perbaikan selesai."
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tindakan.required_if' => 'Tindakan yang dilakukan wajib diisi untuk menandai perbaikan selesai.',
            'tindakan.max' => 'Tindakan yang dilakukan maksimal 1200 karakter.',
            'suku_cadang.max' => 'Suku cadang yang diganti maksimal 600 karakter.',
            'kondisi_perangkat.in' => 'Kondisi perangkat harus berupa Baik, Rusak Ringan, atau Rusak Berat.',
            'foto_sebelum.max' => 'Foto Sebelum maksimal 10 berkas.',
            'foto_selama.max' => 'Foto Selama maksimal 10 berkas.',
            'foto_sesudah.max' => 'Foto Sesudah maksimal 10 berkas.',
            'foto_sebelum.*.mimes' => 'Foto Sebelum harus berformat JPG, JPEG, atau PNG.',
            'foto_selama.*.mimes' => 'Foto Selama harus berformat JPG, JPEG, atau PNG.',
            'foto_sesudah.*.mimes' => 'Foto Sesudah harus berformat JPG, JPEG, atau PNG.',
            'foto_sebelum.*.max' => 'Ukuran setiap foto Sebelum maksimal 2 MB.',
            'foto_selama.*.max' => 'Ukuran setiap foto Selama maksimal 2 MB.',
            'foto_sesudah.*.max' => 'Ukuran setiap foto Sesudah maksimal 2 MB.',
            'foto_sebelum.*.file' => 'Foto Sebelum gagal diunggah.',
            'foto_selama.*.file' => 'Foto Selama gagal diunggah.',
            'foto_sesudah.*.file' => 'Foto Sesudah gagal diunggah.',
        ];
    }
}
