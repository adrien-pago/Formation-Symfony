<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserFixtures extends Fixture
{
    private const USERS = [
        [
            'username' => 'adrien',
            'password' => 'adrien',
            'is_admin' => true,
        ],
        [
            'username' => 'max',
            'password' => 'max',
            'is_admin' => false,
        ],
    ];

    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $userDetails) {
            $user = (new User())
                ->setUsername($userDetails['username'])
                ->setPassword($this->hasherFactory->getPasswordHasher(User::class)->hash($userDetails['password']))
            ;

            if (true === $userDetails['is_admin']) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $manager->persist($user);
        }

        $manager->flush();
    }
}
