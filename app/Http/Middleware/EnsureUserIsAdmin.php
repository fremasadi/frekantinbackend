<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Jika belum login, lanjutkan dulu (akan ditangani oleh auth middleware)
        if (!auth()->check()) {
            return $next($request);
        }

        // Jika sudah login, tapi bukan admin, tolak
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
