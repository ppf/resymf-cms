<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CronJob;
use App\Form\CronJobType;
use App\Repository\CronJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin Cron Job CRUD Controller.
 *
 * Handles scheduled task management in the admin area
 */
#[Route('/admin/cron-jobs')]
#[IsGranted('ROLE_ADMIN')]
class CronJobController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CronJobRepository $cronJobRepository,
    ) {
    }

    /**
     * List all cron jobs (Vue grid).
     */
    #[Route('', name: 'admin_cron_job_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/cron_job/index.html.twig');
    }

    /**
     * Create a new cron job.
     */
    #[Route('/new', name: 'admin_cron_job_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $cronJob = new CronJob();
        $cronJob->setIsActive(true);

        $form = $this->createForm(CronJobType::class, $cronJob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($cronJob);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Cron job "%s" has been created successfully.', $cronJob->getName()));

            return $this->redirectToRoute('admin_cron_job_index');
        }

        return $this->render('admin/cron_job/new.html.twig', [
            'cronJob' => $cronJob,
            'form' => $form,
        ]);
    }

    /**
     * Show cron job details including last output.
     */
    #[Route('/{id}', name: 'admin_cron_job_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CronJob $cronJob): Response
    {
        return $this->render('admin/cron_job/show.html.twig', [
            'cronJob' => $cronJob,
        ]);
    }

    /**
     * Edit existing cron job.
     */
    #[Route('/{id}/edit', name: 'admin_cron_job_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, CronJob $cronJob): Response
    {
        $form = $this->createForm(CronJobType::class, $cronJob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Cron job "%s" has been updated successfully.', $cronJob->getName()));

            return $this->redirectToRoute('admin_cron_job_index');
        }

        return $this->render('admin/cron_job/edit.html.twig', [
            'cronJob' => $cronJob,
            'form' => $form,
        ]);
    }

    /**
     * Delete cron job.
     */
    #[Route('/{id}/delete', name: 'admin_cron_job_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, CronJob $cronJob): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cronJob->getId(), $request->request->get('_token'))) {
            $name = $cronJob->getName();
            $this->entityManager->remove($cronJob);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Cron job "%s" has been deleted successfully.', $name));
        } else {
            $this->addFlash('error', 'Invalid CSRF token. Cron job was not deleted.');
        }

        return $this->redirectToRoute('admin_cron_job_index');
    }

    /**
     * Toggle active status.
     */
    #[Route('/{id}/toggle-active', name: 'admin_cron_job_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Request $request, CronJob $cronJob): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $cronJob->getId(), $request->request->get('_token'))) {
            $cronJob->setIsActive(!$cronJob->getIsActive());
            $this->entityManager->flush();

            $status = $cronJob->getIsActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', sprintf('Cron job "%s" has been %s.', $cronJob->getName(), $status));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_cron_job_index');
    }

    /**
     * Manually run a cron job.
     */
    #[Route('/{id}/run', name: 'admin_cron_job_run', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function run(Request $request, CronJob $cronJob, \Symfony\Component\Messenger\MessageBusInterface $messageBus): Response
    {
        if ($this->isCsrfTokenValid('run' . $cronJob->getId(), $request->request->get('_token'))) {
            $messageBus->dispatch(new \App\Message\CronJobMessage($cronJob->getId()));
            $this->addFlash('success', sprintf('Cron job "%s" has been triggered.', $cronJob->getName()));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_cron_job_show', ['id' => $cronJob->getId()]);
    }
}
