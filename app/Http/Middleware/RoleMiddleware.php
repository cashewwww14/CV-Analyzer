<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (auth()->user()->role !== $role) {
            return response()->json(['error' => "Access denied. {$role} role required"], 403);
        }

        return $next($request);
    }
}