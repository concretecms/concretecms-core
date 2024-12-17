<?php

namespace Concrete\Core\Board\Command;

use Concrete\Core\Application\Application;
use Concrete\Core\Board\DataSource\Driver\Manager as BoardDataSourceManager;
use Concrete\Core\Board\DataSource\Driver\NotifierAwareDriverInterface;
use Doctrine\ORM\EntityManager;

class AddObjectToRelevantBoardInstancesCommandHandler
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

    public function __invoke(AddObjectToRelevantBoardInstancesCommand $command)
    {
        $driver = $this->boardDataSourceManager->driver($command->getDriver());
        if ($driver instanceof NotifierAwareDriverInterface) {
            $notifier = $driver->getBoardInstanceNotifier();
            $instances = $notifier->findBoardInstancesThatMayContainObject($command->getObject());
            foreach ($instances as $instance) {
                $regenerateCommand = new RegenerateBoardInstanceCommand();
                $regenerateCommand->setDefer(true);
                $regenerateCommand->setInstance($instance);
                $this->app->executeCommand($regenerateCommand);
            }
        }
    }

}
