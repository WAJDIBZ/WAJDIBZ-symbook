<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setRoles(['ROLE_ADMIN']);

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setMotDePasse($hashedPassword);

        // Date de création définie automatiquement dans le constructeur
        $admin->setAdresse('Administration Centrale');

        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setRoles(['ROLE_USER']);

        $hashedUserPassword = $this->passwordHasher->hashPassword($user, 'User123!');
        $user->setMotDePasse($hashedUserPassword);
        $user->setAdresse('123 Rue de Test, 1000 Tunis');

        $manager->persist($user);
        $manager->flush();
    }
}
