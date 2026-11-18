<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportCommentController extends Controller
{
    /**
     * Tampilkan formulir pemberian tanggapan penanganan (UC-20 / PBI 20).
     */
    public function create(Report $report): View|RedirectResponse
    {
        $user = auth()->user();

        // 1. Pastikan pengguna yang login adalah pemilik laporan (Pelapor) atau Admin.
        if ($report->id_pelapor !== $user->id_pengguna && ! $user->hasRole('Admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memberikan tanggapan pada laporan ini.');
        }

        // 2. Pastikan status laporan adalah 'Selesai'.
        if ($report->status?->nama_status !== 'Selesai') {
            return redirect()->route('dashboard')
                ->with('error', 'Tanggapan hanya dapat diberikan setelah laporan dinyatakan Selesai.');
        }

        // 3. Eager load relasi pendukung untuk ringkasan laporan dan teknisi.
        $report->load([
            'device.deviceType',
            'room.building',
            'status',
            'reporter',
            'latestAssignment.technician',
            'latestAssignment.progressUpdates.documents',
        ]);

        // 4. Cari tanggapan yang sudah pernah dibuat oleh pelapor untuk laporan ini (jika ada).
        $existingComment = $report->comments()
            ->where('id_pengguna', $user->id_pengguna)
            ->latest('waktu_tanggapan')
            ->first();

        return view('reports.tanggapan', [
            'report' => $report,
            'existingComment' => $existingComment,
        ]);
    }

    /**
     * Simpan tanggapan dan penilaian baru dari pelapor.
     */
    public function store(Request $request, Report $report): RedirectResponse
    {
        $user = auth()->user();

        // 1. Otorisasi pemilik laporan
        if ($report->id_pelapor !== $user->id_pengguna && ! $user->hasRole('Admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memberikan tanggapan pada laporan ini.');
        }

        // 2. Validasi status laporan harus Selesai
        if ($report->status?->nama_status !== 'Selesai') {
            return redirect()->route('dashboard')
                ->with('error', 'Tanggapan hanya dapat diberikan pada laporan yang berstatus Selesai.');
        }

        // 3. Validasi input formulir tanggapan
        $validated = $request->validate([
            'kesesuaian' => ['required', 'in:sudah_sesuai,belum_sesuai'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['required', 'string', 'max:2000'],
        ], [
            'kesesuaian.required' => 'Pilih konfirmasi apakah perbaikan sudah sesuai atau belum.',
            'kesesuaian.in' => 'Pilihan kesesuaian tidak valid.',
            'rating.required' => 'Pilih penilaian layanan berupa rating bintang 1–5.',
            'rating.integer' => 'Nilai rating tidak valid.',
            'rating.min' => 'Rating layanan minimal 1 bintang.',
            'rating.max' => 'Rating layanan maksimal 5 bintang.',
            'komentar.required' => 'Komentar masukan wajib diisi.',
            'komentar.max' => 'Komentar maksimal 2.000 karakter.',
        ]);

        DB::transaction(function () use ($validated, $report, $user) {
            // Format isi_tanggapan (Opsi B: teks terstruktur)
            $labelKesesuaian = $validated['kesesuaian'] === 'sudah_sesuai' ? 'Sudah Sesuai' : 'Belum Sesuai';
            $stars = str_repeat('★', (int) $validated['rating']) . str_repeat('☆', 5 - (int) $validated['rating']);
            $isiTanggapan = sprintf(
                "[%s] Penilaian: %s (%d/5)\n%s",
                $labelKesesuaian,
                $stars,
                $validated['rating'],
                trim($validated['komentar'])
            );

            // Simpan tanggapan via method kirim() sesuai Class Diagram
            Comment::kirim($user, $report, $isiTanggapan);

            // Tangani alur kesesuaian:
            if ($validated['kesesuaian'] === 'belum_sesuai') {
                // Sesuai konfirmasi: kembalikan verifikasi ke admin
                $statusVerifikasi = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();

                $report->update([
                    'id_status' => $statusVerifikasi->id_status,
                ]);

                StatusHistory::create([
                    'id_laporan' => $report->id_laporan,
                    'id_status' => $statusVerifikasi->id_status,
                    'id_pengubah' => $user->id_pengguna,
                    'waktu_perubahan' => now(),
                    'catatan_perubahan' => sprintf(
                        'Pelapor menyatakan perbaikan belum sesuai (Rating: %d/5): "%s". Laporan dibuka kembali untuk verifikasi ulang admin.',
                        $validated['rating'],
                        trim($validated['komentar'])
                    ),
                ]);
            } else {
                // Sudah sesuai -> laporan ditutup/tetap berstatus Selesai, catat di riwayat
                StatusHistory::create([
                    'id_laporan' => $report->id_laporan,
                    'id_status' => $report->id_status,
                    'id_pengubah' => $user->id_pengguna,
                    'waktu_perubahan' => now(),
                    'catatan_perubahan' => sprintf(
                        'Pelapor mengonfirmasi hasil perbaikan sudah sesuai (Rating: %d/5): "%s". Laporan ditutup.',
                        $validated['rating'],
                        trim($validated['komentar'])
                    ),
                ]);
            }
        });

        $message = $validated['kesesuaian'] === 'belum_sesuai'
            ? 'Tanggapan berhasil dikirim. Laporan telah dikembalikan ke antrean Admin untuk verifikasi dan tindak lanjut perbaikan.'
            : 'Tanggapan dan penilaian Anda berhasil disimpan. Terima kasih atas kerja samanya!';

        return redirect()->route('reports.comments.create', $report)->with('success', $message);
    }

    /**
     * Perbarui tanggapan yang telah dikirim (PBI 20: memperbarui tanggapan).
     */
    public function update(Request $request, Report $report, Comment $comment): RedirectResponse
    {
        $user = auth()->user();

        // Otorisasi kepemilikan tanggapan dan laporan
        if ($comment->id_pengguna !== $user->id_pengguna || $comment->id_laporan !== $report->id_laporan) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah tanggapan ini.');
        }

        $validated = $request->validate([
            'kesesuaian' => ['required', 'in:sudah_sesuai,belum_sesuai'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['required', 'string', 'max:2000'],
        ], [
            'kesesuaian.required' => 'Pilih konfirmasi apakah perbaikan sudah sesuai atau belum.',
            'rating.required' => 'Pilih penilaian layanan berupa rating bintang 1–5.',
            'komentar.required' => 'Komentar masukan wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $comment, $report, $user) {
            $labelKesesuaian = $validated['kesesuaian'] === 'sudah_sesuai' ? 'Sudah Sesuai' : 'Belum Sesuai';
            $stars = str_repeat('★', (int) $validated['rating']) . str_repeat('☆', 5 - (int) $validated['rating']);
            $isiTanggapan = sprintf(
                "[%s] Penilaian: %s (%d/5)\n%s",
                $labelKesesuaian,
                $stars,
                $validated['rating'],
                trim($validated['komentar'])
            );

            $comment->update([
                'isi_tanggapan' => $isiTanggapan,
                'waktu_tanggapan' => now(),
            ]);

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $report->id_status,
                'id_pengubah' => $user->id_pengguna,
                'waktu_perubahan' => now(),
                'catatan_perubahan' => sprintf(
                    'Pelapor memperbarui tanggapan penanganan (%s, Rating: %d/5): "%s".',
                    $labelKesesuaian,
                    $validated['rating'],
                    trim($validated['komentar'])
                ),
            ]);
        });

        return redirect()->route('reports.comments.create', $report)
            ->with('success', 'Tanggapan Anda berhasil diperbarui.');
    }

    /**
     * Hapus tanggapan yang telah dikirim (PBI 20: menghapus tanggapan).
     */
    public function destroy(Report $report, Comment $comment): RedirectResponse
    {
        $user = auth()->user();

        if ($comment->id_pengguna !== $user->id_pengguna || $comment->id_laporan !== $report->id_laporan) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus tanggapan ini.');
        }

        DB::transaction(function () use ($comment, $report, $user) {
            $comment->delete();

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $report->id_status,
                'id_pengubah' => $user->id_pengguna,
                'waktu_perubahan' => now(),
                'catatan_perubahan' => 'Pelapor menghapus tanggapan penanganan yang sebelumnya diberikan.',
            ]);
        });

        return redirect()->route('reports.comments.create', $report)
            ->with('success', 'Tanggapan berhasil dihapus.');
    }
}
