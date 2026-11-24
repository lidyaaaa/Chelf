<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Middleware untuk membatasi hak akses halaman berdasarkan role ('admin' atau 'user').
     * - Jika belum login -> kembalikan 401 (Unauthorized)
     * - Jika sudah login tapi role berbeda -> kembalikan 403 (Forbidden)
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Anda harus login untuk mengakses halaman ini.');
        }

        // Bandingkan role user yang login dengan parameter role pada route (misal role:admin)
        if ($user->role !== $role) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        return $next($request);
    }
}
