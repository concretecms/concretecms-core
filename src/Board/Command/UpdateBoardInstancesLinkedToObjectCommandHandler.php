<?php

namespace Concrete\Core\Board\Command;

use Concrete\Core\Application\Application;
use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Board\DataSource\Driver\NotifierAwareDriverInterface;
use Doctrine\ORM\EntityManager;
use Concrete\Core\Board\DataSource\Driver\Manager as BoardDataSourceManager;
class UpdateBoardInstancesLinkedToObjectCommandHandler
{
    
    /**
     * @var EntityManager 
     */
    protected $entityManager;

    /**
     * @var BoardDataSourceManager
     */
    protected $boardDataSourceManager;

    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app, BoardDataSourceManager $boardDataSourceManager, EntityManager $entityManager)
    {
        $this->app = $app;
        $this->boardDataSourceManager = $boardDataSourceManager;
        $this->entityManager = $entityManager;
    }

    public function __invoke(UpdateBoardInstancesLinkedToObjectCommand $command)
    {
        $driver = $this->boardDataSourceManager->driver($command->getDriver());
        if ($driver instanceof NotifierAwareDriverInterface) {
            $notifier = $driver->getBoardInstanceNotifier();
            $instances = $notifier->findBoardInstancesThatMayContainObject($command->getObject());
            foreach ($instances as $instance) {

            }
        }
    }

    
}
