<?php

namespace App\DataFixtures;

use App\Entity\Images;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class ImagesFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private SluggerInterface $slugger){}

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create("fr_FR");
        for($img = 1; $img <= 25; $img ++){
            $images = new Images();
            $images->setName($faker->image(null,640,480));
            //On va chercher une référence de produit 
            $product = $this->getReference("prod-".rand(1, 10));
            $images->setProducts($product);
            // $this->setReference("img-".$img, $images);
            $manager->persist($images);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductsFixtures::class 
        ];
    }
        
}
