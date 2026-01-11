<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentSecurityPolicyMiddleware implements HttpKernelInterface
{
    private HttpKernelInterface $httpKernel;

    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = true): Response
    {
        $response = $this->httpKernel->handle($request, $type, $catch);

        // Only apply to HTML responses
        if ($response->headers->get('Content-Type') && strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            // Remove any existing CSP headers
            $response->headers->remove('Content-Security-Policy');
            $response->headers->remove('Content-Security-Policy-Report-Only');
            $response->headers->remove('X-Content-Security-Policy');
            $response->headers->remove('X-WebKit-CSP');

            // Set our comprehensive CSP
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https://js.stripe.com https://m.stripe.network https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.datatables.net https://maxcdn.bootstrapcdn.com; " .
                   "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://maxcdn.bootstrapcdn.com https://cdn.datatables.net; " .
                   "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://maxcdn.bootstrapcdn.com; " .
                   "img-src 'self' data: https: blob:; " .
                   "connect-src 'self' https://js.stripe.com https://m.stripe.network https://api.stripe.com https: wss:; " .
                   "frame-src 'self' https://js.stripe.com https://m.stripe.network; " .
                   "object-src 'none'; " .
                   "base-uri 'self'; " .
                   "form-action 'self'; " .
                   "media-src 'self' https:;";

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
