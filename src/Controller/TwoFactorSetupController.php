<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles TOTP 2FA setup flow.
 */
#[Route('/2fa-setup')]
class TwoFactorSetupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[Route('', name: '2fa_setup', methods: ['GET', 'POST'])]
    public function setup(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // If 2FA already enabled, redirect to dashboard
        if ($user->isTotpEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is already enabled.');

            return $this->redirectToRoute('admin_dashboard');
        }

        // Generate secret if not exists
        if (!$user->getTotpSecret()) {
            $secret = $this->totpAuthenticator->generateSecret();
            $user->setTotpSecret($secret);
            $this->entityManager->flush();
        }

        // Handle verification POST
        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');

            if ($this->totpAuthenticator->checkCode($user, $code)) {
                $user->setIsTotpEnabled(true);
                $this->entityManager->flush();

                $this->addFlash('success', 'Two-factor authentication enabled successfully.');

                return $this->redirectToRoute('admin_dashboard');
            }

            $this->addFlash('error', 'Invalid verification code. Please try again.');
        }

        // Generate QR code
        $qrContent = $this->totpAuthenticator->getQRContent($user);
        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 10,
        );
        $qrCode = $builder->build();

        return $this->render('security/2fa_setup.html.twig', [
            'qrCode' => $qrCode->getDataUri(),
            'secret' => $user->getTotpSecret(),
        ]);
    }
}
