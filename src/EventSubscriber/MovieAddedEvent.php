<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Movie as MovieEntity;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class MovieAddedEvent extends Event
{
    public function __construct(
        public readonly MovieEntity $movie,
        public readonly UserInterface $user,
        public readonly DateTimeImmutable $at,
    ) {
    }
}
