<?php
// src/EventSubscriber/RegionSubscriber.php
namespace App\EventSubscriber;

use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class RegionSubscriber implements EventSubscriberInterface
{
    private $em;
    private $twig;

    public function __construct(EntityManagerInterface $em, Environment $twig)
    {
        $this->em = $em;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $regions = $this->em->getRepository(Region::class)->findAll();
        $this->twig->addGlobal('regions', $regions);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}