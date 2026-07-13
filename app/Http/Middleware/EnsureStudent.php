<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restricts the student portal to authenticated users with the `student` role. */
class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isStudent()) {
            abort(403, 'Esta sección es solo para alumnos.');
        }

        return $next($request);
    }
}
