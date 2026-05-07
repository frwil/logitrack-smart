<?php
// src/Service/NotificationService.php
namespace App\Service;

use App\Entity\User;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DocumentVehiculeRepository;
use App\Repository\VidangeVehiculeRepository;
use App\Repository\VoyageRepository;
use App\Repository\VehiculeRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class NotificationService
{
    private $em;
    private $documentRepository;
    private $vidangeRepository;
    private $voyageRepository;
    private $vehiculeRepository;
    private $router;
    private $cache;

    public function __construct(
        EntityManagerInterface $em,
        DocumentVehiculeRepository $documentRepository,
        VidangeVehiculeRepository $vidangeRepository,
        VoyageRepository $voyageRepository,
        VehiculeRepository $vehiculeRepository,
        RouterInterface $router
    ) {
        $this->em = $em;
        $this->documentRepository = $documentRepository;
        $this->vidangeRepository = $vidangeRepository;
        $this->voyageRepository = $voyageRepository;
        $this->vehiculeRepository = $vehiculeRepository;
        $this->router = $router;
        $this->cache = new FilesystemAdapter();
    }

    public function createNotification(User $user, string $message, string $type = 'info', ?string $link = null, ?string $category = null): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setLink($link);
        
        if ($category) {
            $notification->setCategory($category);
        }

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function getUnreadNotifications(User $user): array
    {
        return $this->em->getRepository(Notification::class)->findBy([
            'user' => $user,
            'isRead' => false
        ], ['createdAt' => 'DESC']);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->em->flush();
    }

    public function markAllAsRead(User $user): void
    {
        $this->em->getRepository(Notification::class)->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function generateAutomaticNotifications(User $user): void
    {
        $preferences = $user->getNotificationPreferences();

        if ($preferences['documents'] ?? true) {
            $this->checkExpiringDocuments($user);
        }

        if ($preferences['maintenance'] ?? true) {
            $this->checkUpcomingMaintenance($user);
        }

        if ($preferences['trips'] ?? true) {
            $this->checkUpcomingTrips($user);
        }

        if ($preferences['system'] ?? true) {
            $this->generateSystemNotifications($user);
        }
    }

    public function checkExpiringDocuments(User $user): void
    {
        // Documents expirant dans moins de 30 jours
        $expiringDocuments = $this->documentRepository->findExpiringDocuments(30);

        foreach ($expiringDocuments as $document) {
            $vehicule = $document->getVehicule();
            $daysLeft = $document->getDateExpiration()->diff(new \DateTime())->days;

            $message = sprintf(
                'Le document "%s" du véhicule %s expire dans %d jours.',
                $document->getTypeDocument(),
                $vehicule->getImmatriculation(),
                $daysLeft
            );

            $link = $this->router->generate('app_document_vehicule_show', ['id' => $document->getId()]);

            $this->createNotification($user, $message, 'warning', $link, 'documents');
        }
    }

    public function checkUpcomingMaintenance(User $user): void
    {
        // Vidanges prévues selon le kilométrage
        $upcomingMaintenance = $this->vidangeRepository->findUpcomingMaintenance();

        foreach ($upcomingMaintenance as $vidange) {
            $vehicule = $vidange->getAffectation()->getVehicule();
            $kmLeft = $vidange->getKilometrageProchaineVidange() - $vehicule->getKilometrageActuel();

            if ($kmLeft < 1000) {
                $message = sprintf(
                    'Vidange prévue pour le véhicule %s dans %d km.',
                    $vehicule->getImmatriculation(),
                    $kmLeft
                );

                $link = $this->router->generate('app_vidange_vehicule_show', ['id' => $vidange->getId()]);

                $this->createNotification($user, $message, 'info', $link, 'maintenance');
            }
        }
    }

    public function checkUpcomingTrips(User $user): void
    {
        // Voyages planifiés pour le lendemain
        $tomorrow = new \DateTime('tomorrow');
        $upcomingTrips = $this->voyageRepository->findByDateVoyage($tomorrow);

        foreach ($upcomingTrips as $trip) {
            $message = sprintf(
                'Voyage prévu pour demain: %s',
                $trip->getTitre()
            );

            $link = $this->router->generate('app_voyage_show', ['id' => $trip->getId()]);

            $this->createNotification($user, $message, 'info', $link, 'trips');
        }
    }

    public function generateSystemNotifications(User $user): void
    {
        // Alertes système (espace disque, sauvegardes, etc.)
        $this->checkDiskSpace($user);
        $this->checkBackups($user);
        $this->checkSystemHealth($user);
    }

    private function checkDiskSpace(User $user): void
    {
        // Vérification de l'espace disque
        $freeSpace = disk_free_space("/");
        $totalSpace = disk_total_space("/");
        $freePercent = ($freeSpace / $totalSpace) * 100;

        if ($freePercent < 10) {
            $message = sprintf(
                'Espace disque critique: %.2f%% restant (%s libres sur %s).',
                $freePercent,
                $this->formatBytes($freeSpace),
                $this->formatBytes($totalSpace)
            );
            
            $this->createNotification($user, $message, 'danger', null, 'system');
        } elseif ($freePercent < 20) {
            $message = sprintf(
                'Espace disque faible: %.2f%% restant (%s libres sur %s).',
                $freePercent,
                $this->formatBytes($freeSpace),
                $this->formatBytes($totalSpace)
            );
            
            $this->createNotification($user, $message, 'warning', null, 'system');
        }
    }

    private function checkBackups(User $user): void
    {
        // Vérification de la dernière sauvegarde
        $lastBackup = $this->cache->getItem('last_backup_date');
        
        if (!$lastBackup->isHit()) {
            $message = 'Aucune sauvegarde récente enregistrée.';
            $this->createNotification($user, $message, 'warning', null, 'system');
        } else {
            $lastBackupDate = $lastBackup->get();
            $daysSinceBackup = (new \DateTime())->diff($lastBackupDate)->days;

            if ($daysSinceBackup > 7) {
                $message = sprintf('Dernière sauvegarde remonte à %d jours.', $daysSinceBackup);
                $this->createNotification($user, $message, 'warning', null, 'system');
            }
        }
    }

    private function checkSystemHealth(User $user): void
    {
        // Vérification de la santé du système
        $load = sys_getloadavg();
        
        if ($load[0] > 0.8) {
            $message = sprintf('Charge système élevée: %.2f (1min)', $load[0]);
            $this->createNotification($user, $message, 'warning', null, 'system');
        }

        // Vérification de la mémoire
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryPercent = $memoryUsage / $this->convertToBytes($memoryLimit) * 100;

        if ($memoryPercent > 80) {
            $message = sprintf('Utilisation mémoire élevée: %.2f%%', $memoryPercent);
            $this->createNotification($user, $message, 'warning', null, 'system');
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function convertToBytes($value): int
    {
        $unit = strtoupper(substr($value, -1));
        $value = (int) substr($value, 0, -1);
        
        switch ($unit) {
            case 'G': $value *= 1024;
            case 'M': $value *= 1024;
            case 'K': $value *= 1024;
        }
        
        return $value;
    }
}