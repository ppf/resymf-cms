<?php

declare(strict_types=1);

namespace App\Controller;

use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Handles 2FA verification during login.
 */
class TwoFactorController extends AbstractController
{
    #[Route('/2fa', name: '2fa_login')]
    public function form(AuthenticationUtils $authUtils, TwoFactorFirewallContext $context): Response
    {
        // Get the exception (error) from the 2FA authentication attempt
        $error = $authUtils->getLastAuthenticationError();

        return $this->render('security/2fa_login.html.twig', [
            'authenticationError' => $error?->getMessageKey(),
            'authenticationErrorData' => $error?->getMessageData() ?? [],
            'authCodeParameterName' => '_auth_code',
        ]);
    }
}
