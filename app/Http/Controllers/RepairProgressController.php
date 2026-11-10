<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\RepairProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RepairProgressController extends Controller
{
    /**
     * Menampilkan daftar penugasan beserta progres perbaikan terakhirnya.
     */
    public function index(): View
    {
        $user = Auth::user();

        $assignments = Assignment::query()
            ->with([
                'report.device',
                'report.room',
                'report.status',
                'report.reporter',
                'technician',
                'latestProgress',
            ])
            ->withCount('progressUpdates')
            ->when($user->hasRole('Teknisi'), function ($query) use ($user) {
                $query->where('id_teknisi', $user->id_pengguna);
            })
            ->when($user->hasRole('Pelapor'), function ($query) use ($user) {
                $query->whereHas('report', function ($report) use ($user) {
                    $report->where('id_pelapor', $user->id_pengguna);
                });
            })
            ->orderByDesc('waktu_penugasan')
            ->get();

        return view('progress.index', compact('assignments'));
    }

    /**
     * Menampilkan detail penugasan dan riwayat progres perbaikannya.
     */
    public function show(Assignment $assignment): View
    {
        $this->authorizeView($assignment);

        $assignment->load([
            'report.device',
            'report.room',
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

        return view('progress.show', compact('assignment', 'canManage'));
    }

    /**
     * Menampilkan formulir tambah catatan progres.
     */
    public function create(Assignment $assignment): View
    {
        $this->authorizeManage($assignment);

        $assignment->load(['report.device', 'report.room', 'latestProgress']);

        return view('progress.create', compact('assignment'));
    }

    /**
     * Menyimpan catatan progres baru.
     */
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeManage($assignment);

        $validated = $this->validatedData($request);

        $assignment->progressUpdates()->create($validated);

        return redirect()
            ->route('progress.show', $assignment)
            ->with('success', 'Catatan progres perbaikan berhasil ditambahkan.');
    }

    /**
     * Menampilkan formulir perbarui catatan progres.
     */
    public function edit(Assignment $assignment, RepairProgress $progress): View
    {
        $this->authorizeManage($assignment);
        $this->ensureProgressBelongsToAssignment($assignment, $progress);

        $assignment->load(['report.device', 'report.room']);

        return view('progress.edit', compact('assignment', 'progress'));
    }

    /**
     * Memperbarui catatan progres.
     */
    public function update(Request $request, Assignment $assignment, RepairProgress $progress): RedirectResponse
    {
        $this->authorizeManage($assignment);
        $this->ensureProgressBelongsToAssignment($assignment, $progress);

        $progress->update($this->validatedData($request));

        return redirect()
            ->route('progress.show', $assignment)
            ->with('success', 'Catatan progres perbaikan berhasil diperbarui.');
    }

    /**
     * Validasi input catatan progres.
     *
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'persentase' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_progres' => ['required', 'string', 'max:2000'],
            'waktu_progres' => ['nullable', 'date'],
        ], [
            'persentase.required' => 'Persentase progres wajib diisi.',
            'persentase.integer' => 'Persentase progres harus berupa angka bulat.',
            'persentase.min' => 'Persentase progres minimal 0%.',
            'persentase.max' => 'Persentase progres maksimal 100%.',
            'catatan_progres.required' => 'Catatan progres wajib diisi.',
            'catatan_progres.max' => 'Catatan progres maksimal 2000 karakter.',
            'waktu_progres.date' => 'Waktu progres tidak valid.',
        ]);

        $validated['waktu_progres'] = $validated['waktu_progres'] ?? now();

        return $validated;
    }

    /**
     * Teknisi pemilik penugasan, Admin, dan Pimpinan boleh melihat semua.
     * Pelapor hanya boleh melihat progres laporan miliknya sendiri.
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

    /**
     * Hanya Teknisi yang ditugaskan pada penugasan tersebut yang boleh mengubah progres.
     */
    private function authorizeManage(Assignment $assignment): void
    {
        abort_unless(
            $this->canManage($assignment),
            403,
            'Hanya teknisi yang ditugaskan yang dapat mengelola progres perbaikan ini.'
        );
    }

    private function canManage(Assignment $assignment): bool
    {
        $user = Auth::user();

        return $user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna;
    }

    private function ensureProgressBelongsToAssignment(Assignment $assignment, RepairProgress $progress): void
    {
        abort_if($progress->id_penugasan !== $assignment->id_penugasan, 404);
    }
}
