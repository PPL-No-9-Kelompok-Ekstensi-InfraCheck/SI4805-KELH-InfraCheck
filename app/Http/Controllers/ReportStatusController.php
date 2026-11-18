<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\InfrastructureCategory;
use App\Models\Priority;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Support\ReportStatusPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReportStatusController extends Controller
{
    private const PER_PAGE = 7;

    /**
     * Status yang tidak ditampilkan pada halaman pemantauan.
     *
     * @var list<string>
     */
    private const HIDDEN_STATUSES = ['Dibatalkan'];

    /**
     * Menampilkan daftar laporan beserta ringkasan jumlah per status.
     */
    public function index(Request $request): View
    {
        $this->validateFilters($request);

        $baseQuery = $this->baseQuery($request);
        $countQuery = $this->baseQuery($request, ignoreStatus: true);

        $counts = (clone $countQuery)
            ->reorder()
            ->selectRaw('id_status, COUNT(*) as total')
            ->groupBy('id_status')
            ->pluck('total', 'id_status');

        $reports = (clone $baseQuery)
            ->with([
                'device',
                'room.building',
                'status',
                'priority',
                'latestAssignment.technician',
            ])
            ->withMax('statusHistories', 'waktu_perubahan')
            ->orderByDesc('status_histories_max_waktu_perubahan')
            ->orderByDesc('id_laporan')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $statuses = ReportStatus::query()
            ->whereIn('nama_status', ReportStatusPresenter::ORDER)
            ->get()
            ->sortBy(fn (ReportStatus $status) => array_search($status->nama_status, ReportStatusPresenter::ORDER, true))
            ->values();

        return view('reports.status', [
            'reports' => $reports,
            'statuses' => $statuses,
            'counts' => $counts,
            'buildings' => Building::orderBy('nama_gedung')->get(),
            'categories' => InfrastructureCategory::orderBy('nama_kategori')->get(),
            'priorities' => Priority::orderBy('id_prioritas')->get(),
            'filters' => $this->filters($request),
        ]);
    }

    /**
     * Menampilkan detail ringkas satu laporan.
     */
    public function show(Report $report): View
    {
        if ($report->status?->nama_status === 'Dibatalkan') {
            abort(404);
        }

        $report->load([
            'reporter',
            'verifier',
            'device.deviceType',
            'room.building',
            'status',
            'urgency',
            'priority',
            'assignments.technician',
            'assignments.progressUpdates',
            'statusHistories' => fn ($query) => $query->with(['status', 'changer'])->orderBy('waktu_perubahan'),
            'photos',
            'comments' => fn ($query) => $query->with('user')->orderBy('waktu_tanggapan'),
        ]);

        return view('reports.show', compact('report'));
    }

    /**
     * Query dasar laporan yang ditampilkan pada halaman pemantauan.
     */
    private function baseQuery(Request $request, bool $ignoreStatus = false): Builder
    {
        $query = Report::query()
            ->whereHas('status', fn (Builder $statusQuery) => $statusQuery->whereNotIn('nama_status', self::HIDDEN_STATUSES));

        $this->applyFilters($query, $request, $ignoreStatus);

        return $query;
    }

    /**
     * Terapkan seluruh filter yang dipilih pengguna.
     */
    private function applyFilters(Builder $query, Request $request, bool $ignoreStatus = false): void
    {
        if ($request->filled('gedung')) {
            $query->whereHas('room', fn (Builder $roomQuery) => $roomQuery->where('id_gedung', $request->integer('gedung')));
        }

        if ($request->filled('kategori')) {
            $query->whereHas('device.deviceType', fn (Builder $typeQuery) => $typeQuery->where('id_kategori', $request->integer('kategori')));
        }

        if ($request->filled('prioritas')) {
            $query->where('id_prioritas', $request->integer('prioritas'));
        }

        if (! $ignoreStatus && $request->filled('status')) {
            $query->where('id_status', $request->integer('status'));
        }

        if ($request->filled('periode_mulai')) {
            $query->where(
                'waktu_laporan',
                '>=',
                Carbon::parse($request->input('periode_mulai'), 'Asia/Jakarta')->startOfDay()->utc()
            );
        }

        if ($request->filled('periode_selesai')) {
            $query->where(
                'waktu_laporan',
                '<=',
                Carbon::parse($request->input('periode_selesai'), 'Asia/Jakarta')->endOfDay()->utc()
            );
        }

        $search = trim((string) $request->input('cari'));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->whereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('nama_perangkat', 'like', '%'.$search.'%'));

                if (preg_match('/^LP-\d{4}-(\d+)$/i', $search, $matches) === 1) {
                    $searchQuery->orWhere('id_laporan', (int) $matches[1]);
                } elseif (ctype_digit($search)) {
                    $searchQuery->orWhere('id_laporan', (int) $search);
                }
            });
        }
    }

    /**
     * Validasi nilai filter sebelum query dijalankan.
     */
    private function validateFilters(Request $request): void
    {
        $request->validate([
            'gedung' => ['nullable', 'integer', 'exists:buildings,id_gedung'],
            'kategori' => ['nullable', 'integer', 'exists:infrastructure_categories,id_kategori'],
            'prioritas' => ['nullable', 'integer', 'exists:priorities,id_prioritas'],
            'status' => ['nullable', 'integer', 'exists:report_statuses,id_status'],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date'],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        if ($request->filled('periode_mulai') && $request->filled('periode_selesai')
            && $request->date('periode_selesai')->lt($request->date('periode_mulai'))) {
            throw ValidationException::withMessages([
                'periode_selesai' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            ]);
        }
    }

    /**
     * Nilai filter yang perlu dipertahankan pada form dan tautan.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'gedung' => $request->input('gedung'),
            'kategori' => $request->input('kategori'),
            'prioritas' => $request->input('prioritas'),
            'status' => $request->input('status'),
            'periode_mulai' => $request->input('periode_mulai'),
            'periode_selesai' => $request->input('periode_selesai'),
            'cari' => $request->input('cari'),
        ];
    }
}
