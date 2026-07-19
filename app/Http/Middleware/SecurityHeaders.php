<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = [
            'X-Frame-Options' => (string) config('security.headers.x_frame_options', 'SAMEORIGIN'),
            'X-Content-Type-Options' => (string) config('security.headers.x_content_type_options', 'nosniff'),
            'Referrer-Policy' => (string) config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'),
            'Permissions-Policy' => (string) config('security.headers.permissions_policy'),
            'Cross-Origin-Opener-Policy' => (string) config('security.headers.cross_origin_opener_policy', 'same-origin'),
            'Cross-Origin-Resource-Policy' => (string) config('security.headers.cross_origin_resource_policy', 'same-site'),
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        foreach ($headers as $key => $value) {
            if ($value !== '') {
                $response->headers->set($key, $value);
            }
        }

        if (app()->isProduction() && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
