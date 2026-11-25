<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgressRequest;
use App\Models\Assignment;
use App\Models\RepairProgress;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgressController extends Controller
{
    /**
     * Menampilkan antarmuka pembaruan progres teknisi & tahapan stepper.
     */
    public function show(Assignment $assignment): View
    {
        $this->authorizeView($assignment);

        $assignment->load([
            'report.device',
            'report.room.building',
            'report.status',
            'report.urgency',
            'report.priority',
            'report.reporter',
            'technician',
            'assigningAdmin',
            'schedule',
            'progressUpdates' => fn ($query) => $query->with('documents'),
        ]);

        $canManage = $this->canManage($assignment);

        // Jika view technician/progress/show tersedia, gunakan view tersebut
        if (view()->exists('technician.progress.show')) {
            return view('technician.progress.show', compact('assignment', 'canManage'));
        }

        return view('progress.show', compact('assignment', 'canManage'));
    }

    /**
     * Menyimpan pembaruan progres dan memperbarui status laporan secara atomik.
     */
    public function store(StoreProgressRequest $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        DB::transaction(function () use ($assignment, $validated, $user) {
            $waktuProgres = $validated['waktu_progres'] ?? now();
            $persentase = (int) $validated['persentase'];
            $catatanProgres = $validated['catatan_progres'];

            // 1. Simpan catatan progres baru
            $assignment->progressUpdates()->create([
                'persentase' => $persentase,
                'catatan_progres' => $catatanProgres,
                'waktu_progres' => $waktuProgres,
            ]);

            // 2. Evaluasi perubahan status laporan
            $report = $assignment->report;
            if ($report) {
                $targetStatusName = null;

                if ($persentase === 100) {
                    // Ketika 100%, status menjadi 'Menunggu Validasi' (tidak langsung Selesai)
                    $targetStatusName = 'Menunggu Validasi';
                } elseif ($persentase > 0) {
                    // Progres > 0% dan < 100%, status menjadi 'Sedang Dikerjakan'
                    $targetStatusName = 'Sedang Dikerjakan';
                }

                if ($targetStatusName) {
                    $newStatus = ReportStatus::firstOrCreate(['nama_status' => $targetStatusName]);

                    if ($report->id_status !== $newStatus->id_status) {
                        $report->update(['id_status' => $newStatus->id_status]);

                        // Catat histori perubahan status secara atomik
                        StatusHistory::create([
                            'id_laporan' => $report->id_laporan,
                            'id_status' => $newStatus->id_status,
                            'id_pengubah' => $user->id_pengguna,
                            'waktu_perubahan' => now(),
                            'catatan_perubahan' => "Progres perbaikan mencapai {$persentase}%: {$catatanProgres}",
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('progress.show', $assignment)
            ->with('success', 'Catatan progres perbaikan berhasil disimpan.');
    }

    /**
     * Otorisasi tampilan penugasan progres.
     */
    private function authorizeView(Assignment $assignment): void
    {
        if ($this->canManage($assignment)) {
            return;
        }

        $user = Auth::user();

        if ($user->hasRole('Admin') || $user->hasRole('Pimpinan')) {
            return;
        }

        if ($user->hasRole('Pelapor') && $assignment->report?->id_pelapor === $user->id_pengguna) {
            return;
        }

        abort(403, 'Anda tidak berhak melihat progres perbaikan ini.');
    }

    private function canManage(Assignment $assignment): bool
    {
        $user = Auth::user();

        return $user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna;
    }
}
