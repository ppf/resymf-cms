<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\CronJob;
use App\Message\CronJobMessage;
use App\Repository\CronJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API Controller for Cron Job management.
 */
#[Route('/api/cron-jobs')]
#[IsGranted('ROLE_ADMIN')]
class CronJobApiController extends AbstractController
{
    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * List all cron jobs with pagination and search.
     */
    #[Route('/list', name: 'api_cron_jobs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'asc');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('perPage', 20)));

        $validSorts = ['id', 'name', 'command', 'cronExpression', 'isActive', 'lastStatus', 'lastRunAt', 'createdAt'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'name';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->cronJobRepository->createQueryBuilder('c')
            ->orderBy('c.' . $sort, $order);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.command) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        // Count total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        // Paginate
        $results = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $data = array_map(static fn (CronJob $job) => [
            'id' => $job->getId(),
            'name' => $job->getName(),
            'description' => $job->getDescription(),
            'command' => $job->getCommand(),
            'cronExpression' => $job->getCronExpression(),
            'isActive' => $job->getIsActive(),
            'lastStatus' => $job->getLastStatus(),
            'lastRunAt' => $job->getLastRunAt()?->format('c'),
            'createdAt' => $job->getCreatedAt()->format('c'),
        ], $results);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Get single cron job with full details including output.
     */
    #[Route('/{id}', name: 'api_cron_jobs_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $job = $this->cronJobRepository->find($id);

        if (!$job) {
            return $this->json(['error' => 'Cron job not found'], 404);
        }

        return $this->json([
            'id' => $job->getId(),
            'name' => $job->getName(),
            'description' => $job->getDescription(),
            'command' => $job->getCommand(),
            'cronExpression' => $job->getCronExpression(),
            'isActive' => $job->getIsActive(),
            'lastStatus' => $job->getLastStatus(),
            'lastOutput' => $job->getLastOutput(),
            'lastRunAt' => $job->getLastRunAt()?->format('c'),
            'createdAt' => $job->getCreatedAt()->format('c'),
            'updatedAt' => $job->getUpdatedAt()?->format('c'),
        ]);
    }

    /**
     * Toggle active status.
     */
    #[Route('/{id}/toggle-active', name: 'api_cron_jobs_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(int $id, Request $request): JsonResponse
    {
        $job = $this->cronJobRepository->find($id);

        if (!$job) {
            return $this->json(['error' => 'Cron job not found'], 404);
        }

        // Verify CSRF token (generic token for API)
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('cron_job_api', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $job->setIsActive(!$job->getIsActive());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'isActive' => $job->getIsActive(),
            'message' => sprintf('Cron job "%s" %s', $job->getName(), $job->getIsActive() ? 'activated' : 'deactivated'),
        ]);
    }

    /**
     * Manually run a cron job.
     */
    #[Route('/{id}/run', name: 'api_cron_jobs_run', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function run(int $id, Request $request): JsonResponse
    {
        $job = $this->cronJobRepository->find($id);

        if (!$job) {
            return $this->json(['error' => 'Cron job not found'], 404);
        }

        // Verify CSRF token (generic token for API)
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('cron_job_api', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // Dispatch message for immediate execution
        $this->messageBus->dispatch(new CronJobMessage($job->getId()));

        return $this->json([
            'success' => true,
            'message' => sprintf('Cron job "%s" has been triggered', $job->getName()),
        ]);
    }

    /**
     * Delete cron job.
     */
    #[Route('/{id}', name: 'api_cron_jobs_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $job = $this->cronJobRepository->find($id);

        if (!$job) {
            return $this->json(['error' => 'Cron job not found'], 404);
        }

        // Verify CSRF token (generic token for API)
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('cron_job_api', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $name = $job->getName();
        $this->entityManager->remove($job);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('Cron job "%s" has been deleted', $name),
        ]);
    }
}
