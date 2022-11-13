<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Professional;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * @var Generator
     */
    private Generator $faker;

    /**
     * Classe Hasheant le password
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;


    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->faker = Factory::create("fr_FR");
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $userNumber = 10;

        // Authenticated Admin
        $password = "password";
        $adminUser = new User();
        $adminUser->setUsername("admin")
        ->setRoles(["ROLE_ADMIN"])
        ->setPassword($this->userPasswordHasher->hashPassword($adminUser, $password));
        $manager->persist($adminUser);
        

        // Authenticated Users
        for ($i = 0 ; $i < $userNumber ; $i++) {
            $userUser = new User();
            $password = $this->faker->password(2, 6);
            $userUser->setUsername($this->faker->userName() . '@' . $password)
            ->setRoles(["ROLE_USER"])
            ->setPassword($this->userPasswordHasher->hashPassword($userUser, $password));
            $manager->persist($userUser);
        }
        $listCompany = [];


        for ($loop = 0 ; $loop < 4 ; $loop++)
        {
            // Définition d'un job
            $job = $this->faker->jobTitle();

            // Création d'une Company ayant pour activité le $job
            $company = new Company();
            $company->setName($this->faker->company())
            ->setJob($job)
            ->setStatus('on')
            ->setNoteAvg(rand(1, 100)/10)
            ->setNoteCount(random_int(1, 10));
            $manager->persist($company);
            $listCompany[$job] = $company;
        }

        // Création de 5 Professionels ayant pour activité $job
        $manager->flush();
        foreach ($listCompany as $key => $value) {
            for ($i = 0 ; $i < 5 ; $i++)
            {
                $professional = new Professional();
                $professional->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setJob($key)
                ->setStatus('on')
                ->setCompanyJobId($value->getId())
                ->setNoteAvg(rand(10, 100)/10)
                ->setNoteCount(random_int(1, 10));
                $manager->persist($professional);
            }
        }              
        $manager->flush();
    }
}
