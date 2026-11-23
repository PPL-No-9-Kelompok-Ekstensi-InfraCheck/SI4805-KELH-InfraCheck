<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use App\Models\InfrastructureCategory;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Support\ReportStatusPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportHistoryController extends Controller
{
    private const PER_PAGE = 10;

    /**
     * Halaman daftar riwayat penanganan (tab Per Laporan & Per Perangkat).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->input('tab', 'laporan');

        if ($tab === 'perangkat') {
            return $this->indexPerPerangkat($request, $user);
        }

        return $this->indexPerLaporan($request, $user);
    }

    /**
     * Halaman detail riwayat penanganan satu laporan (timeline).
     */
    public function show(Report $report): View
    {
        $user = Auth::user();
        $this->authorizeAccess($user, $report);

        $report->load([
            'reporter',
            'verifier',
            'device.deviceType.category',
            'device.room.building',
            'room.building',
            'status',
            'urgency',
            'priority',
            'photos',
            'assignments.technician',
            'assignments.progressUpdates',
            'statusHistories' => fn ($query) => $query->with(['status', 'changer'])->orderBy('waktu_perubahan'),
            'comments' => fn ($query) => $query->with('user')->orderBy('waktu_tanggapan'),
        ]);

        // Ringkasan penanganan
        $ringkasan = $this->buildRingkasan($report);

        // Riwayat laporan perangkat yang sama (12 bulan terakhir)
        $riwayatPerangkat = $this->riwayatPerangkat($report);

        return view('histories.show', compact('report', 'ringkasan', 'riwayatPerangkat'));
    }

    /**
     * Tab "Per Laporan": daftar laporan yang memiliki riwayat status.
     */
    private function indexPerLaporan(Request $request, $user): View
    {
        $query = $this->baseQuery($user);

        $this->applyFilters($query, $request);

        $reports = $query
            ->with([
                'device.deviceType',
                'room.building',
                'status',
                'priority',
                'reporter',
                'latestAssignment.technician',
            ])
            ->withCount('statusHistories')
            ->withMax('statusHistories', 'waktu_perubahan')
            ->orderByDesc('status_histories_max_waktu_perubahan')
            ->orderByDesc('id_laporan')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('histories.index', [
            'tab' => 'laporan',
            'reports' => $reports,
            'buildings' => Building::orderBy('nama_gedung')->get(),
            'categories' => InfrastructureCategory::orderBy('nama_kategori')->get(),
            'statuses' => ReportStatus::orderBy('id_status')->get(),
            'filters' => $this->currentFilters($request),
        ]);
    }

    /**
     * Tab "Per Perangkat": daftar perangkat yang pernah dilaporkan.
     */
    private function indexPerPerangkat(Request $request, $user): View
    {
        $deviceQuery = Device::query()
            ->whereHas('reports', function (Builder $reportQuery) use ($user) {
                $this->applyRoleScope($reportQuery, $user);
            })
            ->with(['deviceType.category', 'room.building']);

        // Filter berdasarkan kategori
        if ($request->filled('kategori')) {
            $deviceQuery->whereHas('deviceType', fn (Builder $q) => $q->where('id_kategori', $request->integer('kategori')));
        }

        // Filter berdasarkan gedung
        if ($request->filled('gedung')) {
            $deviceQuery->whereHas('room', fn (Builder $q) => $q->where('id_gedung', $request->integer('gedung')));
        }

        // Pencarian
        $search = trim((string) $request->input('cari'));
        if ($search !== '') {
            $deviceQuery->where(function (Builder $q) use ($search) {
                $q->where('nama_perangkat', 'like', '%' . $search . '%')
                  ->orWhere('kode_aset', 'like', '%' . $search . '%');
            });
        }

        $devices = $deviceQuery
            ->withCount(['reports' => function (Builder $q) use ($user) {
                $this->applyRoleScope($q, $user);
                $q->where('waktu_laporan', '>=', now()->subMonths(12));
            }])
            ->orderByDesc('reports_count')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('histories.index', [
            'tab' => 'perangkat',
            'devices' => $devices,
            'buildings' => Building::orderBy('nama_gedung')->get(),
            'categories' => InfrastructureCategory::orderBy('nama_kategori')->get(),
            'statuses' => collect(),
            'filters' => $this->currentFilters($request),
        ]);
    }

    /**
     * Query dasar laporan yang sudah difilter sesuai role pengguna.
     */
    private function baseQuery($user): Builder
    {
        $query = Report::query();
        $this->applyRoleScope($query, $user);

        return $query;
    }

    /**
     * Menerapkan batasan akses berdasarkan role pengguna.
     */
    private function applyRoleScope(Builder $query, $user): void
    {
        if ($user->hasRole(['Admin', 'Pimpinan'])) {
            return; // Lihat semua
        }

        if ($user->hasRole('Teknisi')) {
            $query->whereHas('assignments', fn (Builder $q) => $q->where('id_teknisi', $user->id_pengguna));
            return;
        }

        // Pelapor: hanya miliknya
        $query->where('id_pelapor', $user->id_pengguna);
    }

    /**
     * Memastikan pengguna berhak melihat laporan ini.
     */
    private function authorizeAccess($user, Report $report): void
    {
        if ($user->hasRole(['Admin', 'Pimpinan'])) {
            return;
        }

        if ($user->hasRole('Teknisi')) {
            abort_unless(
                $report->assignments()->where('id_teknisi', $user->id_pengguna)->exists(),
                403,
                'Anda tidak berhak melihat riwayat laporan ini.'
            );
            return;
        }

        // Pelapor
        abort_unless(
            (int) $report->id_pelapor === (int) $user->id_pengguna,
            403,
            'Anda tidak berhak melihat riwayat laporan ini.'
        );
    }

    /**
     * Menerapkan filter pencarian dan penyaringan.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('gedung')) {
            $query->whereHas('room', fn (Builder $q) => $q->where('id_gedung', $request->integer('gedung')));
        }

        if ($request->filled('kategori')) {
            $query->whereHas('device.deviceType', fn (Builder $q) => $q->where('id_kategori', $request->integer('kategori')));
        }

        if ($request->filled('status')) {
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
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('device', fn (Builder $dq) => $dq->where('nama_perangkat', 'like', '%' . $search . '%'));

                if (preg_match('/^LP-\d{4}-(\d+)$/i', $search, $matches) === 1) {
                    $q->orWhere('id_laporan', (int) $matches[1]);
                } elseif (ctype_digit($search)) {
                    $q->orWhere('id_laporan', (int) $search);
                }
            });
        }
    }

    /**
     * Menyusun data ringkasan penanganan untuk halaman detail.
     */
    private function buildRingkasan(Report $report): array
    {
        $histories = $report->statusHistories;

        // Lokasi
        $lokasi = '';
        if ($report->room) {
            $lokasi = ($report->room->building?->nama_gedung ?? '') . ', ' . $report->room->nama_ruangan;
        }

        // Kategori perangkat
        $kategori = $report->device?->deviceType?->category?->nama_kategori
            ?? $report->device?->deviceType?->nama_jenis
            ?? '-';

        // Durasi penanganan (dari riwayat pertama sampai terakhir)
        $durasi = null;
        if ($histories->count() >= 2) {
            $awal = $histories->first()?->waktu_perubahan;
            $akhir = $histories->last()?->waktu_perubahan;
            if ($awal && $akhir) {
                $durasi = $awal->diff($akhir);
            }
        }

        // Penilaian pelapor (dari komentar dengan rating)
        $penilaian = null;
        $komenPelapor = $report->comments
            ->filter(fn ($c) => (int) $c->id_pengguna === (int) $report->id_pelapor)
            ->first();
        if ($komenPelapor && $komenPelapor->rating !== null) {
            $penilaian = $komenPelapor->rating;
        }

        return [
            'lokasi' => $lokasi,
            'kategori' => $kategori,
            'durasi' => $durasi,
            'penilaian' => $penilaian,
        ];
    }

    /**
     * Mengambil riwayat laporan perangkat yang sama (12 bulan terakhir).
     */
    private function riwayatPerangkat(Report $report): array
    {
        if (! $report->id_perangkat) {
            return ['laporan' => collect(), 'total' => 0];
        }

        $laporan = Report::with('status')
            ->where('id_perangkat', $report->id_perangkat)
            ->where('waktu_laporan', '>=', now()->subMonths(12))
            ->orderByDesc('waktu_laporan')
            ->get();

        return [
            'laporan' => $laporan,
            'total' => $laporan->count(),
        ];
    }

    /**
     * Nilai filter aktif saat ini.
     *
     * @return array<string, mixed>
     */
    private function currentFilters(Request $request): array
    {
        return [
            'gedung' => $request->input('gedung'),
            'kategori' => $request->input('kategori'),
            'status' => $request->input('status'),
            'periode_mulai' => $request->input('periode_mulai'),
            'periode_selesai' => $request->input('periode_selesai'),
            'cari' => $request->input('cari'),
        ];
    }
}
