<?php

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityController extends AbstractController
{
    public function login(IriConverterInterface $iriConverter): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'error' => 'Invalid login request: check that the Content-Type header is "application/json"',
            ], 400);
        }
        /** @var UserInterface $user */
        $user = $this->getUser();

        return new Response(null, 204, ['Location' => $iriConverter->getIriFromItem($user)]);
    }

    public function logout(): void
    {
        throw new \Exception('should not be reached');
    }
}
