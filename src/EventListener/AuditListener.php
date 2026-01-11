<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class AuditListener
{
    public function __construct(
        private Security $security
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // Check if entity has audit fields
        if (method_exists($entity, 'setCreatedBy') && method_exists($entity, 'setUpdatedBy')) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $entity->setCreatedBy($user);
                $entity->setUpdatedBy($user);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Check if entity has audit fields
        if (method_exists($entity, 'setUpdatedBy')) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $entity->setUpdatedBy($user);
            }
        }
    }
}