<?php

namespace App\Http\Middleware;

use App\Services\DemoWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveDemoWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $data = app(DemoWorkspace::class)->data($request);
        if (!$data || ($data['status'] ?? null) !== 'active') {
            return redirect()->route('demo.start')->with('warning', 'Sesi mode uji sudah berakhir. Mulai sesi baru.');
        }
        return $next($request);
    }
}
