<?php

namespace App\DataFixtures;

/**
 * Interface that replaces Doctrine\Common\DataFixtures\DependentFixtureInterface
 * for production environments where DoctrineFixturesBundle is not installed
 */
interface DependentFixtureInterface
{
    /**
     * This method returns an array of fixtures classes that must be loaded before this one.
     *
     * @return string[]
     */
    public function getDependencies(): array;
}
