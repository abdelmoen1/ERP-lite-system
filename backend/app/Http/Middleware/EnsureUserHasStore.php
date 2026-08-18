<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->store_id) {
            return response()->json([
                'message' => 'المستخدم غير مرتبط بمتجر',
            ], 403);
        }

        return $next($request);
    }
}
