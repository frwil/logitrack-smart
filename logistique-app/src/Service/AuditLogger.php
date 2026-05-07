<?php
// src/Service/AuditLogger.php
namespace App\Service;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack
    ) {}

    public function log(
        string $action,
        string $entity,
        int $entityId,
        array $changes = [],
        string $status = 'success'
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : 'unknown';
        
        // Récupérer les informations de région basées sur l'IP
        $regionInfo = $this->getRegionFromIp($ipAddress);

        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setEntity($entity);
        $auditLog->setEntityId($entityId);
        $auditLog->setChanges(array_merge($changes, [
            'ip_address' => $ipAddress,
            'region_info' => $regionInfo
        ]));
        $auditLog->setStatus($status);
        $auditLog->setUser($this->security->getUser());
        $auditLog->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($auditLog);
        $this->em->flush();
    }

    private function getRegionFromIp(string $ipAddress): array
    {
        // Implémentation améliorée avec vérification des plages d'IP privées
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // IP publique - simulation de détection de région
            $ipParts = explode('.', $ipAddress);
            $firstOctet = (int)($ipParts[0] ?? 0);
            
            if ($firstOctet >= 1 && $firstOctet <= 79) {
                return ['type' => 'region', 'name' => 'Région Nord'];
            } elseif ($firstOctet >= 80 && $firstOctet <= 159) {
                return ['type' => 'region', 'name' => 'Région Sud'];
            } elseif ($firstOctet >= 160 && $firstOctet <= 223) {
                return ['type' => 'region', 'name' => 'Région Est'];
            } else {
                return ['type' => 'region', 'name' => 'Région Ouest'];
            }
        } else {
            // IP privée ou localhost
            return ['type' => 'local', 'name' => 'Réseau local'];
        }
    }
}