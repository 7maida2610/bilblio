<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class ErrorController extends AbstractController
{
    public function __construct(
        private Environment $twig
    ) {}

    /**
     * Main error handler that routes to appropriate error pages
     */
    public function show(Request $request, \Throwable $exception = null): Response
    {
        // Get the status code from the request or exception
        $statusCode = $this->getStatusCode($request, $exception);

        switch ($statusCode) {
            case 403:
                return $this->show403($request, $exception);
            case 404:
                return $this->show404($request, $exception);
            case 500:
            default:
                return $this->show500($request, $exception);
        }
    }

    public function show404(Request $request, \Throwable $exception = null): Response
    {
        $content = $this->twig->render('error/404.html.twig');
        return new Response($content, 404);
    }

    public function show500(Request $request, \Throwable $exception = null): Response
    {
        $content = $this->twig->render('error/500.html.twig');
        return new Response($content, 500);
    }

    public function show403(Request $request, \Throwable $exception = null): Response
    {
        $content = $this->twig->render('error/403.html.twig');
        return new Response($content, 403);
    }

    /**
     * Determine the appropriate status code from request/exception
     */
    private function getStatusCode(Request $request, ?\Throwable $exception): int
    {
        // Check for explicit status code in request attributes
        if ($request->attributes->has('exception')) {
            $requestException = $request->attributes->get('exception');
            if ($requestException instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return $requestException->getStatusCode();
            }
        }

        // Check the exception type
        if ($exception instanceof AccessDeniedException) {
            return 403;
        }

        if ($exception instanceof NotFoundHttpException) {
            return 404;
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        // Default to 500 for unknown errors
        return 500;
    }
}