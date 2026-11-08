<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Status laporan yang dianggap masih aktif (perangkat sedang ditangani).
     */
    private const STATUS_AKTIF = [
        'Menunggu Verifikasi',
        'Diverifikasi',
        'Ditugaskan',
        'Sedang Dikerjakan',
        'Menunggu Validasi',
    ];

    /**
     * Menampilkan formulir pengajuan laporan kerusakan oleh Pelapor.
     */
    public function create()
    {
        // Perangkat beserta relasi lokasinya untuk dropdown pilihan perangkat
        $devices = Device::with(['deviceType', 'room.building'])
            ->orderBy('nama_perangkat')
            ->get();

        $urgensiLevels = UrgencyLevel::orderBy('id_urgensi')->get();

        return view('reports.create', compact('devices', 'urgensiLevels'));
    }

    /**
     * Memeriksa apakah perangkat masih memiliki laporan aktif (deteksi laporan ganda).
     * Dipanggil lewat AJAX dari formulir.
     */
    public function check(Request $request)
    {
        $request->validate([
            'id_perangkat' => ['required', 'integer', 'exists:devices,id_perangkat'],
        ]);

        $laporanAktif = $this->cariLaporanAktif((int) $request->input('id_perangkat'));

        if (!$laporanAktif) {
            return response()->json(['duplikat' => false]);
        }

        return response()->json([
            'duplikat' => true,
            'id_laporan' => $laporanAktif->id_laporan,
            'nomor_laporan' => $laporanAktif->nomor_laporan,
            'nama_status' => $laporanAktif->status->nama_status,
        ]);
    }

    /**
     * Menyimpan laporan kerusakan baru beserta foto dokumentasinya.
     */
    public function store(Request $request)
    {
        $pelapor = Auth::user();

        $data = $request->validate([
            'id_perangkat' => ['required', 'integer', 'exists:devices,id_perangkat'],
            'id_urgensi' => ['required', 'integer', 'exists:urgency_levels,id_urgensi'],
            'deskripsi_kerusakan' => ['required', 'string', 'min:10', 'max:5000'],
            'foto' => ['nullable', 'array', 'max:5'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'id_perangkat.required' => 'Perangkat wajib dipilih.',
            'id_perangkat.exists' => 'Perangkat yang dipilih tidak ditemukan.',
            'id_urgensi.required' => 'Tingkat urgensi wajib dipilih.',
            'id_urgensi.exists' => 'Tingkat urgensi yang dipilih tidak valid.',
            'deskripsi_kerusakan.required' => 'Deskripsi kerusakan wajib diisi.',
            'deskripsi_kerusakan.min' => 'Deskripsi kerusakan minimal :min karakter.',
            'deskripsi_kerusakan.max' => 'Deskripsi kerusakan maksimal :max karakter.',
            'foto.max' => 'Maksimal :max foto dokumentasi yang dapat diunggah.',
            'foto.*.image' => 'Berkas harus berupa gambar.',
            'foto.*.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
        ]);

        // Deteksi laporan ganda: tolak jika perangkat masih punya laporan aktif
        if ($this->cariLaporanAktif((int) $data['id_perangkat'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'id_perangkat' => 'Perangkat ini masih memiliki laporan aktif yang sedang ditangani. Laporan ganda tidak dapat diajukan.',
                ]);
        }

        $statusAwal = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();
        $perangkat = Device::findOrFail($data['id_perangkat']);

        $laporan = DB::transaction(function () use ($request, $data, $pelapor, $perangkat, $statusAwal) {
            $laporan = Report::create([
                'id_pelapor' => $pelapor->id_pengguna,
                'id_perangkat' => $perangkat->id_perangkat,
                'id_ruangan' => $perangkat->id_ruangan,
                'id_status' => $statusAwal->id_status,
                'id_urgensi' => $data['id_urgensi'],
                'deskripsi_kerusakan' => $data['deskripsi_kerusakan'],
                'waktu_laporan' => now(),
            ]);

            // Simpan foto dokumentasi kerusakan (jika ada)
            if ($request->hasFile('foto')) {
                $direktori = public_path('uploads/laporan');
                if (!is_dir($direktori)) {
                    mkdir($direktori, 0755, true);
                }

                foreach ($request->file('foto') as $foto) {
                    $namaAsli = preg_replace('/[^A-Za-z0-9\.\-_]/', '', $foto->getClientOriginalName());
                    $namaBerkas = time() . '_' . $laporan->id_laporan . '_' . $namaAsli;

                    $foto->move($direktori, $namaBerkas);

                    ReportPhoto::create([
                        'id_laporan' => $laporan->id_laporan,
                        'lokasi_berkas' => 'uploads/laporan/' . $namaBerkas,
                        'waktu_unggah' => now(),
                    ]);
                }
            }

            return $laporan;
        });

        return redirect()
            ->route('reports.create')
            ->with([
                'status' => 'Laporan kerusakan berhasil diajukan.',
                'nomor_laporan' => $laporan->nomor_laporan,
            ]);
    }

    /**
     * Mencari laporan aktif (belum selesai) pada sebuah perangkat.
     */
    private function cariLaporanAktif(int $idPerangkat): ?Report
    {
        return Report::with('status')
            ->where('id_perangkat', $idPerangkat)
            ->whereHas('status', function ($query) {
                $query->whereIn('nama_status', self::STATUS_AKTIF);
            })
            ->first();
    }
}
