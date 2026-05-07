<?php
// src/Command/GenerateNotificationsCommand.php
namespace App\Command;

use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:generate-notifications',
    description: 'Generate automatic notifications for all users'
)]
class GenerateNotificationsCommand extends Command
{
    private $notificationService;
    private $entityManager;

    public function __construct(NotificationService $notificationService, EntityManagerInterface $entityManager)
    {
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $this->notificationService->generateAutomaticNotifications($user);
            $output->writeln("Notifications générées pour l'utilisateur: " . $user->getEmail());
        }

        $output->writeln('Toutes les notifications ont été générées.');

        return Command::SUCCESS;
    }
}