<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCors
{
    public function handle(Request $request, Closure $next): Response
    {
        // Preflight request
        if ($request->isMethod('OPTIONS')) {
            return $this->withCorsHeaders(response('', 204));
        }

        $response = $next($request);

        return $this->withCorsHeaders($response);
    }

    private function withCorsHeaders(Response $response): Response
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', '*');

        return $response;
    }
}
