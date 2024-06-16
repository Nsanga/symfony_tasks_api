<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class PasswordHasherListener
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    // This method is called before a new entity is persisted
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only act on User entities
        if (!$entity instanceof User) {
            return; // Exit if the entity is not an instance of User
        }

        // Hash the password of the User entity before persisting
        $this->hashPassword($entity);
    }

    // This method is called before an entity is updated
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only act on User entities
        if (!$entity instanceof User) {
            return; // Exit if the entity is not an instance of User
        }

        // Hash the password of the User entity before updating
        $this->hashPassword($entity);

        // Necessary to force the update to see the change
        $em = $args->getEntityManager();
        $meta = $em->getClassMetadata(get_class($entity));
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $entity);
    }

    // Helper method to hash the password of a User entity
    private function hashPassword(User $user): void
    {
        $plainPassword = $user->getPassword(); // Get the plain password from the entity
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword); // Hash the password
        $user->setPassword($hashedPassword); // Set the hashed password back to the entity
    }
}
