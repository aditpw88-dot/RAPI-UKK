<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has admin/waka role
        if (auth()->check() && auth()->user()->role_id !== null) {
            $role = auth()->user()->role()->first();
            $roleName = strtolower($role->role_name ?? '');
            if ($role && (str_contains($roleName, 'admin') || str_contains($roleName, 'waka'))) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki hak akses. Hanya admin/waka yang dapat mengelola user.');
    }
}
