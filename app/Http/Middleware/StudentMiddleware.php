<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'student') {
            return response()->json([
                'message' => 'Forbidden. Student access only.'
            ], 403);
        }

        return $next($request);
    }
}