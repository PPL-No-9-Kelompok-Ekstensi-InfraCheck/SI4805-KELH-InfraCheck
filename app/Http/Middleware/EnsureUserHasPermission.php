<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Memastikan pengguna aktif memiliki salah satu hak akses yang diminta.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->akun_aktif,
            403,
            'Akun Anda tidak aktif atau Anda belum masuk.'
        );

        abort_unless(
            $user->hasAnyPermission($permissions),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );

        return $next($request);
    }
}
