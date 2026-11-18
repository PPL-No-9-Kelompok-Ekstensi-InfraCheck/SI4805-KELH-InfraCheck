<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\RepairSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RepairScheduleController extends Controller
{
    /**
     * Menampilkan kalender jadwal perbaikan mingguan.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Pelapor tidak memiliki akses ke halaman kalender kerja
        if ($user->hasRole('Pelapor')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat jadwal perbaikan.');
        }

        Carbon::setLocale('id');

        // 1. Tentukan tanggal acuan minggu
        $weekInput = $request->query('week');
        try {
            $baseDate = $weekInput ? Carbon::parse($weekInput) : now();
        } catch (\Exception $e) {
            $baseDate = now();
        }

        $startOfWeek = (clone $baseDate)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = (clone $startOfWeek)->addDays(4)->endOfDay(); // Senin s/d Jumat

        // Label rentang minggu (misal: "12 – 16 Oktober 2026")
        $startDay = $startOfWeek->isoFormat('D');
        $endDay = (clone $startOfWeek)->addDays(4)->isoFormat('D MMMM Y');
        $weekLabel = "{$startDay} – {$endDay}";

        // Navigasi minggu
        $prevWeek = (clone $startOfWeek)->subWeek()->format('Y-m-d');
        $nextWeek = (clone $startOfWeek)->addWeek()->format('Y-m-d');
        $currentWeek = now()->format('Y-m-d');

        // 2. Susun hari Senin s/d Jumat
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

        // Slot jam kerja sesuai mockup (08:00, 09:00, 10:00, 11:00, 13:00, 14:00, 15:00)
        $hourSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00'];

        // 3. Filter Teknisi
        $selectedTechnicianId = null;
        if ($user->hasRole('Teknisi')) {
            $selectedTechnicianId = $user->id_pengguna;
        } elseif ($request->filled('technician_id')) {
            $selectedTechnicianId = (int) $request->query('technician_id');
        }

        // Ambil daftar teknisi untuk dropdown filter
        $technicians = User::whereHas('role', function ($q) {
            $q->where('nama_peran', 'Teknisi');
        })->where('akun_aktif', true)->orderBy('nama')->get();

        // 4. Query jadwal pada rentang minggu terpilih
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

        // Petakan palet warna untuk teknisi
        $colorStyles = $this->assignTechnicianColors($technicians);

        // Petakan jadwal ke dalam slot [date][hour_slot]
        $gridSchedules = [];
        foreach ($schedules as $sched) {
            if (! $sched->waktu_mulai) {
                continue;
            }

            $dateKey = $sched->waktu_mulai->format('Y-m-d');
            $hourInt = (int) $sched->waktu_mulai->format('H');

            // Cari slot jam terdekat yang sesuai
            $slotKey = $this->resolveHourSlot($hourInt, $hourSlots);

            $gridSchedules[$dateKey][$slotKey][] = $sched;
        }

        // 5. Penugasan yang belum dijadwalkan (antrean untuk dijadwalkan cepat)
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

        return view('schedules.index', compact(
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
        ));
    }

    /**
     * Menampilkan formulir buat jadwal baru.
     */
    public function create(Request $request): View
    {
        $this->authorizeCreate();

        $user = Auth::user();
        $selectedAssignmentId = $request->query('assignment_id');

        // Ambil penugasan yang belum memiliki jadwal
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
                // Pastikan teknisi hanya bisa memilih penugasan miliknya
                if ($user->hasRole('Teknisi') && $selectedAssignment->id_teknisi !== $user->id_pengguna) {
                    abort(403, 'Anda tidak berhak menjadwalkan penugasan milik teknisi lain.');
                }

                // Jika sudah ada jadwal, arahkan ke edit
                if ($selectedAssignment->schedule) {
                    return redirect()->route('schedules.edit', $selectedAssignment->schedule)
                        ->with('info', 'Penugasan ini sudah memiliki jadwal perbaikan. Anda dapat memperbaruinya di sini.');
                }
            }
        }

        return view('schedules.create', compact('availableAssignments', 'selectedAssignment'));
    }

    /**
     * Menyimpan jadwal perbaikan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreate();

        $user = Auth::user();

        $validated = $request->validate([
            'id_penugasan' => [
                'required',
                'exists:assignments,id_penugasan',
                'unique:repair_schedules,id_penugasan',
            ],
            'waktu_mulai' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'catatan_jadwal' => ['nullable', 'string', 'max:1000'],
        ], [
            'id_penugasan.required' => 'Pilih penugasan yang akan dijadwalkan.',
            'id_penugasan.exists' => 'Penugasan yang dipilih tidak valid.',
            'id_penugasan.unique' => 'Penugasan ini sudah memiliki jadwal perbaikan.',
            'waktu_mulai.required' => 'Waktu mulai perbaikan wajib diisi.',
            'waktu_mulai.date' => 'Format waktu mulai tidak valid.',
            'target_selesai.required' => 'Target selesai perbaikan wajib diisi.',
            'target_selesai.date' => 'Format target selesai tidak valid.',
            'target_selesai.after_or_equal' => 'Target selesai harus sama dengan atau setelah waktu mulai.',
            'catatan_jadwal.max' => 'Catatan jadwal maksimal 1000 karakter.',
        ]);

        $assignment = Assignment::findOrFail($validated['id_penugasan']);

        // Jika teknisi, pastikan penugasan miliknya
        if ($user->hasRole('Teknisi') && $assignment->id_teknisi !== $user->id_pengguna) {
            abort(403, 'Anda tidak berhak menjadwalkan penugasan milik teknisi lain.');
        }

        $schedule = RepairSchedule::create($validated);

        $weekParam = Carbon::parse($schedule->waktu_mulai)->format('Y-m-d');

        return redirect()->route('schedules.index', ['week' => $weekParam])
            ->with('success', 'Jadwal perbaikan berhasil disimpan.');
    }

    /**
     * Menampilkan formulir ubah jadwal perbaikan.
     */
    public function edit(RepairSchedule $schedule): View
    {
        $this->authorizeManage($schedule);

        $schedule->load(['assignment.report.device', 'assignment.report.room.building', 'assignment.technician']);

        return view('schedules.edit', compact('schedule'));
    }

    /**
     * Memperbarui jadwal perbaikan.
     */
    public function update(Request $request, RepairSchedule $schedule): RedirectResponse
    {
        $this->authorizeManage($schedule);

        $validated = $request->validate([
            'waktu_mulai' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'catatan_jadwal' => ['nullable', 'string', 'max:1000'],
        ], [
            'waktu_mulai.required' => 'Waktu mulai perbaikan wajib diisi.',
            'waktu_mulai.date' => 'Format waktu mulai tidak valid.',
            'target_selesai.required' => 'Target selesai perbaikan wajib diisi.',
            'target_selesai.date' => 'Format target selesai tidak valid.',
            'target_selesai.after_or_equal' => 'Target selesai harus sama dengan atau setelah waktu mulai.',
            'catatan_jadwal.max' => 'Catatan jadwal maksimal 1000 karakter.',
        ]);

        $schedule->update($validated);

        $weekParam = Carbon::parse($schedule->waktu_mulai)->format('Y-m-d');

        return redirect()->route('schedules.index', ['week' => $weekParam])
            ->with('success', 'Jadwal perbaikan berhasil diperbarui.');
    }

    /**
     * Membatalkan jadwal perbaikan (menghapus entri jadwal).
     */
    public function destroy(RepairSchedule $schedule): RedirectResponse
    {
        $this->authorizeManage($schedule);

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal perbaikan berhasil dibatalkan.');
    }

    /**
     * Validasi hak akses untuk membuat jadwal.
     */
    private function authorizeCreate(): void
    {
        $user = Auth::user();
        if (! $user->hasRole('Admin') && ! $user->hasRole('Teknisi')) {
            abort(403, 'Hanya Admin dan Teknisi yang berhak mengatur jadwal perbaikan.');
        }
    }

    /**
     * Validasi hak akses untuk mengelola (update/destroy) jadwal.
     */
    private function authorizeManage(RepairSchedule $schedule): void
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

    /**
     * Menetapkan kombinasi warna kartu kalender per teknisi.
     *
     * @param \Illuminate\Support\Collection<int, User> $technicians
     * @return array<int, array{bg: string, border: string, text: string, dot: string}>
     */
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

    /**
     * Memetakan jam mulai ke slot jam kalender.
     *
     * @param int $hour
     * @param array<int, string> $slots
     * @return string
     */
    private function resolveHourSlot(int $hour, array $slots): string
    {
        $formatted = sprintf('%02d:00', $hour);
        if (in_array($formatted, $slots, true)) {
            return $formatted;
        }

        // Jika sebelum 08:00
        if ($hour < 8) {
            return '08:00';
        }

        // Jika jam 12:00, masukkan ke 13:00 atau 11:00
        if ($hour === 12) {
            return '13:00';
        }

        // Jika setelah 15:00
        if ($hour >= 15) {
            return '15:00';
        }

        return '08:00';
    }
}
