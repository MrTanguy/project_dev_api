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
        $this->faker = Factory::create();
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

        $companyList = [];
        for ($i = 0 ; $i < 5 ; $i++)
        {
            $company = new Company();
            $company->setName($this->faker->company())
            ->setJob($this->faker->jobTitle());
            $manager->persist($company);
            $companyList[] = $company;
        }

        for ($i = 0 ; $i < 20 ; $i++)
        {
            $professional = new Professional();
            $professional->setFirstname($this->faker->firstName())
            ->setLastname($this->faker->lastName())
            ->setJob($this->faker->jobTitle())
            ->setStatus('on');
            $manager->persist($professional);
        }

        //$company_job_id

        $manager->flush();
    }
}
