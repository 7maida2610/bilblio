<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private Environment $twig
    ) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Render the 403 error template directly
        $content = $this->twig->render('error/403.html.twig');

        return new Response($content, 403);
    }
}