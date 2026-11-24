<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAuthenticatedUser
{
    /**
     * Middleware untuk memastikan user sudah login sebelum mengakses halaman.
     * Jika belum login, sistem menampilkan halaman error 401 (Unauthorized).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah sesi login user ada; jika tidak ada, tolak dengan kode 401
        if (! $request->user()) {
            abort(401, 'Anda harus login untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
