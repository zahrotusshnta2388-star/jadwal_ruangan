<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Cek jika user sudah login
        if (Auth::check()) {
            // Jika sudah login, cek role
            if (Auth::user()->role !== 'admin') {
                abort(403, 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
            }
        } else {
            // Jika belum login, redirect ke login
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}
