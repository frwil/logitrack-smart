<?php
// src/Controller/NotificationController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\NotificationService;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[Route('/notification')]
class NotificationController extends AbstractController
{
    private $notificationRepository;
    private $entityManager;

    public function __construct(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager)
    {
        $this->notificationRepository = $notificationRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/ajax', name: 'app_notification_ajax', methods: ['GET'])]
    public function ajaxNotifications(NotificationService $notificationService, Request $request): JsonResponse
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est connecté ET que c'est une requête AJAX
        if (!$user || !$request->isXmlHttpRequest()) {
            return $this->json([
                'notifications' => [],
                'count' => 0
            ]);
        }

        $notifications = $notificationService->getUnreadNotifications($user);

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'link' => $notification->getLink(),
                'createdAt' => $notification->getCreatedAt()->format('d/m/Y H:i'),
                'isRead' => $notification->isIsRead(),
                'category' => $notification->getCategory() // Nouveau champ
            ];
        }

        return $this->json([
            'notifications' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/read/{id}', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(int $id, NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $notification = $this->notificationRepository->find($id);

        if ($notification && $notification->getUser() === $user) {
            $notificationService->markAsRead($notification);
            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
    }

    #[Route('/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $notificationService->markAllAsRead($user);
        return $this->json(['success' => true]);
    }

    #[Route('/', name: 'app_notification_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $notifications = $this->notificationRepository->findByUser($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/preferences', name: 'app_notification_preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        return $this->json([
            'preferences' => $user->getNotificationPreferences()
        ]);
    }

    #[Route('/preferences/save', name: 'app_notification_preferences_save', methods: ['POST'])]
    public function savePreferences(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['preferences'])) {
            $user->setNotificationPreferences($data['preferences']);
            $this->entityManager->flush();

            return $this->json(['success' => true]);
        }

        return $this->json(['error' => 'Données invalides'], 400);
    }

    #[Route('/generate/system', name: 'app_notification_generate_system', methods: ['POST'])]
    public function generateSystemNotifications(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        // Générer les notifications système
        $notificationService->generateSystemNotifications($user);

        return $this->json(['success' => true, 'message' => 'Notifications système générées']);
    }

    #[Route('/generate/all', name: 'app_notification_generate_all', methods: ['POST'])]
    public function generateAllNotifications(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        // Générer toutes les notifications automatiques
        $notificationService->generateAutomaticNotifications($user);

        return $this->json(['success' => true, 'message' => 'Toutes les notifications générées']);
    }
}