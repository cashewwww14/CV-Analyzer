<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        return $next($request);
    }
}