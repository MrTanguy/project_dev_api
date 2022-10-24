<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Professional;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\Factory;

class AppFixtures extends Fixture
{
    /**
     * @var Generator
     */
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
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

        $manager->flush();
    }
}
