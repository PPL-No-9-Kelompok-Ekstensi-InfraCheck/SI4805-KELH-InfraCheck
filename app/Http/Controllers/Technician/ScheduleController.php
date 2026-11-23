<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Assignment;
use App\Models\RepairSchedule;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Menampilkan kalender mingguan penanganan perbaikan (sesuai Mockup Gambar 4.5.19).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        if ($user->hasRole('Pelapor')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat jadwal perbaikan.');
        }

        Carbon::setLocale('id');

        $weekInput = $request->query('week');
        try {
            $baseDate = $weekInput ? Carbon::parse($weekInput) : now();
        } catch (\Exception $e) {
            $baseDate = now();
        }

        $startOfWeek = (clone $baseDate)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = (clone $startOfWeek)->addDays(4)->endOfDay();

        $startDay = $startOfWeek->isoFormat('D');
        $endDay = (clone $startOfWeek)->addDays(4)->isoFormat('D MMMM Y');
        $weekLabel = "{$startDay} – {$endDay}";

        $prevWeek = (clone $startOfWeek)->subWeek()->format('Y-m-d');
        $nextWeek = (clone $startOfWeek)->addWeek()->format('Y-m-d');
        $currentWeek = now()->format('Y-m-d');

        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $dayDate = (clone $startOfWeek)->addDays($i);
            $days[] = [
                'date' => $dayDate->format('Y-m-d'),
                'day_name' => strtoupper($dayDate->isoFormat('ddd')),
                'label' => strtoupper($dayDate->isoFormat('ddd D MMM')),
                'is_today' => $dayDate->isToday(),
            ];
        }

        $hourSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00'];

        $selectedTechnicianId = null;
        if ($user->hasRole('Teknisi')) {
            $selectedTechnicianId = $user->id_pengguna;
        } elseif ($request->filled('technician_id')) {
            $selectedTechnicianId = (int) $request->query('technician_id');
        }

        $technicians = User::whereHas('role', function ($q) {
            $q->where('nama_peran', 'Teknisi');
        })->where('akun_aktif', true)->orderBy('nama')->get();

        $schedules = RepairSchedule::query()
            ->with([
                'assignment.report.device',
                'assignment.report.room.building',
                'assignment.technician',
            ])
            ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('waktu_mulai', [$startOfWeek->startOfDay(), $endOfWeek])
                    ->orWhereBetween('target_selesai', [$startOfWeek->startOfDay(), $endOfWeek]);
            })
            ->when($selectedTechnicianId, function ($query) use ($selectedTechnicianId) {
                $query->whereHas('assignment', function ($q) use ($selectedTechnicianId) {
                    $q->where('id_teknisi', $selectedTechnicianId);
                });
            })
            ->orderBy('waktu_mulai')
            ->get();

        $colorStyles = $this->assignTechnicianColors($technicians);

        $gridSchedules = [];
        foreach ($schedules as $sched) {
            if (! $sched->waktu_mulai) {
                continue;
            }

            $dateKey = $sched->waktu_mulai->format('Y-m-d');
            $hourInt = (int) $sched->waktu_mulai->format('H');
            $slotKey = $this->resolveHourSlot($hourInt, $hourSlots);

            $gridSchedules[$dateKey][$slotKey][] = $sched;
        }

        $unscheduledAssignments = Assignment::query()
            ->with(['report.device', 'report.room.building', 'technician'])
            ->whereDoesntHave('schedule')
            ->when($user->hasRole('Teknisi'), function ($query) use ($user) {
                $query->where('id_teknisi', $user->id_pengguna);
            })
            ->when(($user->hasRole('Admin') || $user->hasRole('Pimpinan')) && $selectedTechnicianId, function ($query) use ($selectedTechnicianId) {
                $query->where('id_teknisi', $selectedTechnicianId);
            })
            ->orderByDesc('waktu_penugasan')
            ->get();

        $canManage = $user->hasRole('Admin') || $user->hasRole('Teknisi');

        $viewData = compact(
            'weekLabel',
            'baseDate',
            'prevWeek',
            'nextWeek',
            'currentWeek',
            'days',
            'hourSlots',
            'technicians',
            'selectedTechnicianId',
            'gridSchedules',
            'colorStyles',
            'unscheduledAssignments',
            'canManage'
        );

        if (view()->exists('technician.schedule.index')) {
            return view('technician.schedule.index', $viewData);
        }

        return view('schedules.index', $viewData);
    }

    /**
     * Manajemen jadwal per penugasan: POST /teknisi/jadwal/{penugasan}
     */
    public function store(StoreScheduleRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeAssignmentManagement($assignment);

        $validated = $request->validated();
        $user = Auth::user();

        DB::transaction(function () use ($assignment, $validated, $user) {
            // Update or create jadwal perbaikan (1-to-1)
            $schedule = $assignment->schedule()->updateOrCreate(
                ['id_penugasan' => $assignment->id_penugasan],
                [
                    'waktu_mulai' => $validated['waktu_mulai'],
                    'target_selesai' => $validated['target_selesai'],
                    'catatan_jadwal' => $validated['catatan_jadwal'] ?? null,
                ]
            );

            // Status laporan diperbarui menjadi 'Dijadwalkan' jika belum masuk tahap pengerjaan
            $report = $assignment->report;
            if ($report && (! $report->status || in_array($report->status->nama_status, ['Ditugaskan', 'Diverifikasi', 'Menunggu Verifikasi']))) {
                $dijadwalkanStatus = ReportStatus::firstOrCreate(['nama_status' => 'Dijadwalkan']);

                if ($report->id_status !== $dijadwalkanStatus->id_status) {
                    $report->update(['id_status' => $dijadwalkanStatus->id_status]);

                    StatusHistory::create([
                        'id_laporan' => $report->id_laporan,
                        'id_status' => $dijadwalkanStatus->id_status,
                        'id_pengubah' => $user->id_pengguna,
                        'waktu_perubahan' => now(),
                        'catatan_perubahan' => 'Jadwal perbaikan telah ditentukan.',
                    ]);
                }
            }
        });

        $weekParam = Carbon::parse($validated['waktu_mulai'])->format('Y-m-d');

        return redirect()->route('schedule.index', ['week' => $weekParam])
            ->with('success', 'Jadwal perbaikan berhasil disimpan.');
    }

    /**
     * Memperbarui jadwal teknisi: PUT /teknisi/jadwal/{penugasan}
     */
    public function update(StoreScheduleRequest $request, Assignment $assignment): RedirectResponse
    {
        return $this->store($request, $assignment);
    }

    /**
     * Tampilan form create umum (admin atau teknisi).
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        if (! $user->hasRole('Admin') && ! $user->hasRole('Teknisi')) {
            abort(403, 'Hanya Admin dan Teknisi yang berhak mengatur jadwal perbaikan.');
        }

        $selectedAssignmentId = $request->query('assignment_id');

        $availableAssignments = Assignment::query()
            ->with(['report.device', 'report.room.building', 'technician'])
            ->whereDoesntHave('schedule')
            ->when($user->hasRole('Teknisi'), function ($query) use ($user) {
                $query->where('id_teknisi', $user->id_pengguna);
            })
            ->orderByDesc('waktu_penugasan')
            ->get();

        $selectedAssignment = null;
        if ($selectedAssignmentId) {
            $selectedAssignment = Assignment::with(['report.device', 'report.room.building', 'technician', 'schedule'])
                ->find($selectedAssignmentId);

            if ($selectedAssignment) {
                if ($user->hasRole('Teknisi') && $selectedAssignment->id_teknisi !== $user->id_pengguna) {
                    abort(403, 'Anda tidak berhak menjadwalkan penugasan milik teknisi lain.');
                }

                if ($selectedAssignment->schedule) {
                    return redirect()->route('schedules.edit', $selectedAssignment->schedule)
                        ->with('info', 'Penugasan ini sudah memiliki jadwal perbaikan.');
                }
            }
        }

        return view('schedules.create', compact('availableAssignments', 'selectedAssignment'));
    }

    /**
     * Simpan jadwal dari form admin / modal umum.
     */
    public function storeScheduleFromAdmin(StoreScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assignment = Assignment::findOrFail($validated['id_penugasan']);

        return $this->store($request, $assignment);
    }

    /**
     * Tampilan form edit jadwal.
     */
    public function edit(RepairSchedule $schedule): View
    {
        $this->authorizeScheduleManagement($schedule);

        $schedule->load(['assignment.report.device', 'assignment.report.room.building', 'assignment.technician']);

        return view('schedules.edit', compact('schedule'));
    }

    /**
     * Update jadwal dari form edit.
     */
    public function updateScheduleFromAdmin(StoreScheduleRequest $request, RepairSchedule $schedule): RedirectResponse
    {
        $this->authorizeScheduleManagement($schedule);

        return $this->store($request, $schedule->assignment);
    }

    /**
     * Batalkan jadwal (hapus record dari repair_schedules).
     */
    public function destroy(RepairSchedule $schedule): RedirectResponse
    {
        $this->authorizeScheduleManagement($schedule);

        $schedule->delete();

        return redirect()->route('schedule.index')
            ->with('success', 'Jadwal perbaikan berhasil dibatalkan.');
    }

    private function authorizeAssignmentManagement(Assignment $assignment): void
    {
        $user = Auth::user();
        if ($user->hasRole('Admin')) {
            return;
        }

        if ($user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna) {
            return;
        }

        abort(403, 'Anda tidak berhak mengelola jadwal perbaikan untuk penugasan ini.');
    }

    private function authorizeScheduleManagement(RepairSchedule $schedule): void
    {
        $user = Auth::user();
        if ($user->hasRole('Admin')) {
            return;
        }

        if ($user->hasRole('Teknisi') && $schedule->assignment?->id_teknisi === $user->id_pengguna) {
            return;
        }

        abort(403, 'Anda tidak berhak mengelola jadwal perbaikan ini.');
    }

    private function assignTechnicianColors($technicians): array
    {
        $palette = [
            ['bg' => 'bg-purple-100/90', 'border' => 'border-purple-600', 'text' => 'text-purple-900', 'dot' => 'bg-purple-600'],
            ['bg' => 'bg-amber-100/90', 'border' => 'border-amber-600', 'text' => 'text-amber-900', 'dot' => 'bg-amber-600'],
            ['bg' => 'bg-blue-100/90', 'border' => 'border-blue-600', 'text' => 'text-blue-900', 'dot' => 'bg-blue-600'],
            ['bg' => 'bg-emerald-100/90', 'border' => 'border-emerald-600', 'text' => 'text-emerald-900', 'dot' => 'bg-emerald-600'],
            ['bg' => 'bg-rose-100/90', 'border' => 'border-rose-600', 'text' => 'text-rose-900', 'dot' => 'bg-rose-600'],
            ['bg' => 'bg-indigo-100/90', 'border' => 'border-indigo-600', 'text' => 'text-indigo-900', 'dot' => 'bg-indigo-600'],
        ];

        $result = [];
        $index = 0;
        foreach ($technicians as $tech) {
            $result[$tech->id_pengguna] = $palette[$index % count($palette)];
            $index++;
        }

        return $result;
    }

    private function resolveHourSlot(int $hour, array $slots): string
    {
        $formatted = sprintf('%02d:00', $hour);
        if (in_array($formatted, $slots, true)) {
            return $formatted;
        }

        if ($hour < 8) {
            return '08:00';
        }

        if ($hour === 12) {
            return '13:00';
        }

        if ($hour >= 15) {
            return '15:00';
        }

        return '08:00';
    }
}
