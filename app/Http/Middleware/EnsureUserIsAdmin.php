<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Jika belum login, lanjutkan dulu (akan ditangani oleh auth middleware)
        if (!auth()->check()) {
            return $next($request);
        }

        // Jika sudah login, tapi bukan admin
        if (auth()->user()->role !== 'admin') {
            // Jika ini adalah permintaan login (misalnya setelah submit form login)
            if ($request->is('admin/login') || $request->routeIs('filament.admin.auth.login')) {
                // Logout pengguna
                Auth::logout();
                
                // Invalidate session dan regenerate token
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect kembali ke halaman login dengan pesan error
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Akses ditolak. Anda tidak memiliki izin admin.');
            }
            
            // Untuk request lainnya, tolak dengan 403
            return abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}