<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin Settings Controller
 *
 * Handles site-wide settings management
 * Uses singleton pattern (only one Settings record exists)
 */
#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsRepository $settingsRepository
    ) {
    }

    /**
     * View and edit settings
     */
    #[Route('', name: 'admin_settings_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        // Get or create the singleton Settings record
        $settings = $this->settingsRepository->getOrCreate();

        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Settings have been updated successfully.');

            return $this->redirectToRoute('admin_settings_edit');
        }

        return $this->render('admin/settings/edit.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }
}
