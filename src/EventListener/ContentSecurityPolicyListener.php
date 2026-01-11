<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentSecurityPolicyListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // Use negative priority to run AFTER other subscribers (to override them)
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Remove all existing CSP headers - we'll set our own comprehensive one
        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        $response->headers->remove('X-Content-Security-Policy');
        $response->headers->remove('X-WebKit-CSP');

        // Comprehensive CSP for development that allows all necessary resources
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
}
