<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';
    protected $primaryKey = 'id_tanggapan';

    protected $fillable = [
        'id_laporan',
        'id_pengguna',
        'isi_tanggapan',
        'waktu_tanggapan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_tanggapan' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'id_laporan', 'id_laporan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Kirim tanggapan baru sesuai Class Diagram InfraCheck (UC-20).
     */
    public static function kirim(User|int $penulis, Report|int $laporan, string $isi): self
    {
        $idPengguna = $penulis instanceof User ? $penulis->id_pengguna : $penulis;
        $idLaporan = $laporan instanceof Report ? $laporan->id_laporan : $laporan;

        return self::create([
            'id_pengguna' => $idPengguna,
            'id_laporan' => $idLaporan,
            'isi_tanggapan' => $isi,
            'waktu_tanggapan' => now(),
        ]);
    }

    /**
     * Parsing isi tanggapan terstruktur (Opsi B: tanpa mengubah struktur tabel ERD).
     * Mendukung format JSON, teks berawalan [Sudah Sesuai]/[Belum Sesuai], maupun teks bebas.
     *
     * @return array{kesesuaian: ?string, rating: ?int, komentar: string}
     */
    public function getParsedDataAttribute(): array
    {
        $text = (string) $this->isi_tanggapan;

        // Cek jika teks berformat JSON
        if (str_starts_with(trim($text), '{') && str_ends_with(trim($text), '}')) {
            $json = json_decode($text, true);
            if (is_array($json) && (isset($json['kesesuaian']) || isset($json['rating']) || isset($json['komentar']))) {
                return [
                    'kesesuaian' => $json['kesesuaian'] ?? null,
                    'rating' => isset($json['rating']) ? (int) $json['rating'] : null,
                    'komentar' => (string) ($json['komentar'] ?? ''),
                ];
            }
        }

        $kesesuaian = null;
        $rating = null;
        $komentar = $text;

        // Parsing tag kesesuaian: [Sudah Sesuai] atau [Belum Sesuai]
        if (preg_match('/^\[(Sudah Sesuai|Belum Sesuai)\]/i', $komentar, $matches)) {
            $kesesuaian = strtolower(str_replace(' ', '_', $matches[1]));
            $komentar = trim(substr($komentar, strlen($matches[0])));
        }

        // Parsing rating: Penilaian: ★★★★★ (5/5) atau Rating: 5/5 atau (5/5)
        if (preg_match('/(?:Penilaian|Rating)?\s*(?:[★☆\s]*)?\(?([1-5])\/5\)?/i', $komentar, $matches)) {
            $rating = (int) $matches[1];
            $komentar = trim(preg_replace('/^(?:Penilaian|Rating)?:?\s*(?:[★☆\s]*)?\(?[1-5]\/5\)?[\r\n]*/i', '', $komentar));
        }

        return [
            'kesesuaian' => $kesesuaian,
            'rating' => $rating,
            'komentar' => $komentar,
        ];
    }

    public function getIsSesuaiAttribute(): ?bool
    {
        $k = $this->parsed_data['kesesuaian'];

        if ($k === 'sudah_sesuai') {
            return true;
        }

        if ($k === 'belum_sesuai') {
            return false;
        }

        return null;
    }

    public function getKesesuaianLabelAttribute(): ?string
    {
        $k = $this->parsed_data['kesesuaian'];

        if ($k === 'sudah_sesuai') {
            return 'Sudah sesuai';
        }

        if ($k === 'belum_sesuai') {
            return 'Belum sesuai';
        }

        return null;
    }

    public function getRatingAttribute(): ?int
    {
        return $this->parsed_data['rating'];
    }

    public function getRatingStarsAttribute(): ?string
    {
        $r = $this->rating;
        if ($r === null) {
            return null;
        }

        $clamped = max(0, min(5, $r));

        return str_repeat('★', $clamped) . str_repeat('☆', 5 - $clamped);
    }

    public function getKomentarBersihAttribute(): string
    {
        return $this->parsed_data['komentar'] !== ''
            ? $this->parsed_data['komentar']
            : (string) $this->isi_tanggapan;
    }
}
