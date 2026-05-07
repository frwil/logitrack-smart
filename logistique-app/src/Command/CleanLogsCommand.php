<?php

namespace App\Command;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:clean-logs',
    description: 'Nettoie les logs de plus de 7 jours'
)]
class CleanLogsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Nettoie les logs de plus de 7 jours');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Supprimer les logs de plus de 7 jours
        $deletedCount = $this->cleanOldLogs();

        $io->success(sprintf('%d logs de plus de 7 jours ont été supprimés.', $deletedCount));

        return Command::SUCCESS;
    }

    private function cleanOldLogs(): int
    {
        $repository = $this->entityManager->getRepository(AuditLog::class);
        $dateLimit = new \DateTimeImmutable('-7 days');

        $query = $repository->createQueryBuilder('a')
            ->delete()
            ->where('a.created_at < :date')
            ->setParameter('date', $dateLimit)
            ->getQuery();

        return $query->execute();
    }
}