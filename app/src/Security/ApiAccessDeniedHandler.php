<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
  public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
  {
    return new JsonResponse([
      'message' => $accessDeniedException->getMessage() ?: 'Access Denied',
    ], Response::HTTP_FORBIDDEN);
  }
}
