<?php

namespace App\EventSubscriber;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function updateLastLoggedIn(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoggedInAt($this->clock->now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => [
                ['updateLastLoggedIn', 0],
            ],
        ];
    }
}
