<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplaceRepairDocumentRequest;
use App\Http\Requests\StoreRepairDocumentRequest;
use App\Models\Assignment;
use App\Models\RepairDocument;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RepairDocumentController extends Controller
{
    /**
     * Menampilkan daftar penugasan untuk pengelolaan / pemantauan dokumentasi hasil perbaikan.
     */
    public function index(): View
    {
        $user = Auth::user();

        if (! $user->hasPermission('repair-documents.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat dokumentasi perbaikan.');
        }

        $assignments = Assignment::query()
            ->with([
                'report.device',
                'report.room.building',
                'report.status',
                'technician',
                'latestProgress',
                'progressUpdates' => fn ($q) => $q->with('documents'),
            ])
            ->when($user->hasRole('Teknisi'), function ($query) use ($user) {
                $query->where('id_teknisi', $user->id_pengguna);
            })
            ->when($user->hasRole('Pelapor'), function ($query) use ($user) {
                $query->whereHas('report', fn ($q) => $q->where('id_pelapor', $user->id_pengguna));
            })
            ->orderByDesc('waktu_penugasan')
            ->get();

        return view('documents.index', compact('assignments'));
    }

    /**
     * Menampilkan halaman formulir dokumentasi hasil perbaikan untuk suatu penugasan.
     * Sesuai dengan Mockup 4.5.20.
     */
    public function create(Assignment $assignment): View
    {
        Gate::authorize('viewAssignment', [RepairDocument::class, $assignment]);

        $assignment->load([
            'report.device',
            'report.room.building',
            'report.status',
            'report.urgency',
            'report.priority',
            'technician',
            'schedule',
            'progressUpdates' => fn ($q) => $q->with('documents')->orderBy('waktu_progres', 'desc'),
        ]);

        $canManage = Gate::allows('upload', [RepairDocument::class, $assignment]);
        $isLocked = (new \App\Policies\RepairDocumentPolicy())->terkunci($assignment);

        // Kumpulkan semua dokumen yang sudah ada pada penugasan ini
        $existingDocuments = RepairDocument::query()
            ->whereHas('progress', fn ($q) => $q->where('id_penugasan', $assignment->id_penugasan))
            ->orderBy('waktu_unggah')
            ->get()
            ->groupBy('jenis_dokumentasi');

        return view('documents.create', compact('assignment', 'canManage', 'isLocked', 'existingDocuments'));
    }

    /**
     * Menyimpan unggahan dokumentasi perbaikan dan memperbarui progres/status laporan.
     */
    public function store(StoreRepairDocumentRequest $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        DB::transaction(function () use ($request, $assignment, $validated, $user) {
            $tandaiSelesai = $request->boolean('tandai_selesai');

            // 1. Tentukan persentase progres
            if ($tandaiSelesai) {
                $persentase = 100;
            } else {
                $persentase = (int) ($assignment->latestProgress?->persentase ?? 0);
            }

            // 2. Susun catatan progres gabungan tindakan & suku cadang (A1 tanpa migrasi tabel)
            $bagian = [];
            if (! empty($validated['tindakan'])) {
                $bagian[] = "Tindakan: " . trim($validated['tindakan']);
            }
            if (! empty($validated['suku_cadang'])) {
                $bagian[] = "Suku cadang: " . trim($validated['suku_cadang']);
            }
            $catatanProgres = ! empty($bagian)
                ? implode("\n", $bagian)
                : 'Unggah dokumentasi hasil perbaikan.';

            // 3. Buat baris progress baru sebagai induk dokumentasi
            $progress = $assignment->progressUpdates()->create([
                'persentase' => $persentase,
                'catatan_progres' => $catatanProgres,
                'waktu_progres' => now(),
            ]);

            // 4. Simpan foto-foto ke file storage dan catat di repair_documents
            foreach (RepairDocument::JENIS as $jenis => $inputName) {
                if ($request->hasFile($inputName)) {
                    foreach ((array) $request->file($inputName) as $file) {
                        $path = $file->store('repair-documents', RepairDocument::DISK);

                        RepairDocument::create([
                            'id_progres' => $progress->id_progres,
                            'lokasi_berkas' => $path,
                            'jenis_dokumentasi' => $jenis,
                            'waktu_unggah' => now(),
                        ]);
                    }
                }
            }

            // 5. Perbarui kondisi perangkat jika dipilih
            if (! empty($validated['kondisi_perangkat']) && $assignment->report?->device) {
                $assignment->report->device->update([
                    'kondisi' => $validated['kondisi_perangkat'],
                ]);
            }

            // 6. Jika ditandai selesai (100%), perbarui status laporan menjadi 'Menunggu Validasi'
            if ($tandaiSelesai) {
                $report = $assignment->report;
                if ($report) {
                    $newStatus = ReportStatus::firstOrCreate(['nama_status' => 'Menunggu Validasi']);

                    if ($report->id_status !== $newStatus->id_status) {
                        $report->update(['id_status' => $newStatus->id_status]);

                        StatusHistory::create([
                            'id_laporan' => $report->id_laporan,
                            'id_status' => $newStatus->id_status,
                            'id_pengubah' => $user->id_pengguna,
                            'waktu_perubahan' => now(),
                            'catatan_perubahan' => 'Perbaikan ditandai selesai oleh teknisi (progres 100%). Menunggu validasi admin.',
                        ]);
                    }
                }
            }
        });

        $pesan = $request->boolean('tandai_selesai')
            ? 'Dokumentasi perbaikan berhasil disimpan dan pekerjaan ditandai selesai (menunggu validasi).'
            : 'Dokumentasi perbaikan berhasil disimpan.';

        return redirect()
            ->route('documents.create', $assignment)
            ->with('success', $pesan);
    }

    /**
     * Mengganti berkas dokumentasi yang salah dengan berkas baru.
     */
    public function update(ReplaceRepairDocumentRequest $request, RepairDocument $document): RedirectResponse
    {
        $assignment = $document->progress->assignment;

        // Hapus berkas lama dari disk public jika ada
        if (Storage::disk(RepairDocument::DISK)->exists($document->lokasi_berkas)) {
            Storage::disk(RepairDocument::DISK)->delete($document->lokasi_berkas);
        }

        // Simpan berkas baru
        $path = $request->file('berkas')->store('repair-documents', RepairDocument::DISK);

        $document->update([
            'lokasi_berkas' => $path,
            'waktu_unggah' => now(),
        ]);

        return redirect()
            ->route('documents.create', $assignment)
            ->with('success', 'Berkas dokumentasi berhasil diperbarui.');
    }

    /**
     * Menghapus berkas dokumentasi.
     */
    public function destroy(RepairDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $assignment = $document->progress->assignment;

        // Hapus berkas fisik jika ada
        if (Storage::disk(RepairDocument::DISK)->exists($document->lokasi_berkas)) {
            Storage::disk(RepairDocument::DISK)->delete($document->lokasi_berkas);
        }

        $document->delete();

        return redirect()
            ->route('documents.create', $assignment)
            ->with('success', 'Dokumentasi perbaikan berhasil dihapus.');
    }
}
