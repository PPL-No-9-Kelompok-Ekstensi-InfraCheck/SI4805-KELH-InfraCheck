<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\RepairDocument;
use App\Models\User;

/**
 * Hak akses dokumentasi hasil perbaikan (PBI 18).
 *
 * Dua lapis: permission (config/permissions.php) lalu kepemilikan —
 * teknisi pemilik penugasan untuk tulis, pemilik laporan untuk Pelapor.
 */
class RepairDocumentPolicy
{
    /** Setelah laporan berstatus ini, dokumentasi terkunci (tidak bisa diubah/dihapus). */
    public const STATUS_TERKUNCI = 'Selesai';

    /**
     * Melihat daftar/halaman dokumentasi suatu penugasan.
     */
    public function viewAssignment(User $user, mixed $modelOrAssignment, ?Assignment $assignment = null): bool
    {
        $targetAssignment = $modelOrAssignment instanceof Assignment ? $modelOrAssignment : $assignment;

        if (! $targetAssignment) {
            return false;
        }

        if (! $user->hasPermission('repair-documents.view')) {
            return false;
        }

        if ($this->pemilikPenugasan($user, $targetAssignment)) {
            return true;
        }

        if ($user->hasRole('Admin') || $user->hasRole('Pimpinan')) {
            return true;
        }

        return $user->hasRole('Pelapor')
            && $targetAssignment->report?->id_pelapor === $user->id_pengguna;
    }

    /**
     * Melihat satu berkas dokumentasi.
     */
    public function view(User $user, RepairDocument $document): bool
    {
        return $this->viewAssignment($user, $document->progress->assignment);
    }

    /**
     * Mengunggah dokumentasi baru pada penugasan.
     */
    public function upload(User $user, mixed $modelOrAssignment, ?Assignment $assignment = null): bool
    {
        $targetAssignment = $modelOrAssignment instanceof Assignment ? $modelOrAssignment : $assignment;

        if (! $targetAssignment) {
            return false;
        }

        return $user->hasPermission('repair-documents.create')
            && $this->pemilikPenugasan($user, $targetAssignment)
            && ! $this->terkunci($targetAssignment);
    }

    /**
     * Mengganti berkas dokumentasi.
     */
    public function update(User $user, RepairDocument $document): bool
    {
        $assignment = $document->progress->assignment;

        return $user->hasPermission('repair-documents.update')
            && $this->pemilikPenugasan($user, $assignment)
            && ! $this->terkunci($assignment);
    }

    /**
     * Menghapus dokumentasi.
     */
    public function delete(User $user, RepairDocument $document): bool
    {
        $assignment = $document->progress->assignment;

        return $user->hasPermission('repair-documents.delete')
            && $this->pemilikPenugasan($user, $assignment)
            && ! $this->terkunci($assignment);
    }

    /**
     * Apakah laporan pada penugasan sudah terkunci (Selesai).
     */
    public function terkunci(Assignment $assignment): bool
    {
        return $assignment->report?->status?->nama_status === self::STATUS_TERKUNCI;
    }

    private function pemilikPenugasan(User $user, Assignment $assignment): bool
    {
        return $user->hasRole('Teknisi') && $assignment->id_teknisi === $user->id_pengguna;
    }
}
