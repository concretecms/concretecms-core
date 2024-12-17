<?php

namespace Concrete\Core\Board\Instance\Notifier;

use Concrete\Core\Entity\Board\Instance;
use Doctrine\ORM\EntityManager;

defined('C5_EXECUTE') or die("Access Denied.");

class PageNotifier implements NotifierInterface
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findBoardInstancesThatMayContainObject($object): array
    {
        return $this->entityManager->getRepository(Instance::class)->findAll();
    }
}

