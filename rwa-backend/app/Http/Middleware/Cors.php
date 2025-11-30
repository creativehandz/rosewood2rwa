<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow requests from your frontend subdomain
        $allowedOrigins = [
            'https://rosewoodestate2.creativehandz.in',
            'http://localhost:3000', // For local development
        ];

        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200, $response->headers->all());
        }

        return $response;
    }
}