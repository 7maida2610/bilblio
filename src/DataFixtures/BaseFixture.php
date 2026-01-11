<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;

/**
 * Base fixture class that replaces Doctrine\Bundle\FixturesBundle\Fixture
 * for production environments where DoctrineFixturesBundle is not installed
 */
abstract class BaseFixture
{
    abstract public function load(ObjectManager $manager): void;
}
