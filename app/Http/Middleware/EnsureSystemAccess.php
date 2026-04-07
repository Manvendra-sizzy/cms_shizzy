<?php

namespace App\Http\Middleware;

use App\Modules\Systems\Models\System;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $system = $request->route('system');
        if ($system instanceof System) {
            if (! $user->hasSystemAccess((int) $system->id)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
