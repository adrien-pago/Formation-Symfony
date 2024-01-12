<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserFixtures extends Fixture
{
    private const USERS = [
        [
            'username' => 'adrien',
            'password' => 'adrien',
            'birthdate' => '10 July',
            'age' => 35,
            'is_admin' => true,
        ],
        [
            'username' => 'max',
            'password' => 'max',
            'birthdate' => '3 Feb',
            'age' => 15,
            'is_admin' => false,
        ],
        [
            'username' => 'lou',
            'password' => 'lou',
            'birthdate' => '22 Dec',
            'age' => 5,
            'is_admin' => false,
        ],
        [
            'username' => 'john',
            'password' => 'john',
            'birthdate' => null,
            'age' => null,
            'is_admin' => false,
        ],
    ];

    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory,
        private readonly ClockInterface $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $userDetails) {
            $user = (new User())
                ->setUsername($userDetails['username'])
                ->setPassword($this->hasherFactory->getPasswordHasher(User::class)->hash($userDetails['password']))
            ;

            if (null !== $userDetails['age']) {
                $birthYear = $this->clock->now()->modify("-{$userDetails['age']} years")->format('Y');
                $birthdate = new DateTimeImmutable("{$userDetails['birthdate']} {$birthYear}");
                $user->setBirthdate($birthdate);
            }

            if (true === $userDetails['is_admin']) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $manager->persist($user);
        }

        $manager->flush();
    }
}
