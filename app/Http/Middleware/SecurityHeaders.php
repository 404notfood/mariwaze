<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (app()->isProduction()) {
            $nonce = Vite::cspNonce();
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "connect-src 'self'",
                "font-src 'self' data:",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "img-src 'self' data: blob: https://*.tile.openstreetmap.org",
                "object-src 'none'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "style-src-attr 'unsafe-inline'",
                'upgrade-insecure-requests',
                'report-uri /api/security/csp-reports',
                'report-to csp-endpoint',
            ]));
            $headers->set('Reporting-Endpoints', 'csp-endpoint="/api/security/csp-reports"');
            $headers->set('Content-Security-Policy-Report-Only', implode('; ', [
                "default-src 'self'",
                "script-src 'nonce-{$nonce}' 'strict-dynamic'",
                "style-src 'self' 'nonce-{$nonce}'",
                "style-src-attr 'none'",
                "img-src 'self' data: blob: https://*.tile.openstreetmap.org",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'none'",
                "frame-ancestors 'none'",
                'report-uri /api/security/csp-reports',
                'report-to csp-endpoint',
            ]));

            if ($request->isSecure()) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
