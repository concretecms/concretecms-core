<?php

namespace Concrete\Core\Board\Command;

use Concrete\Core\Application\Application;
use Concrete\Core\Entity\Board\DataSource\DataSource;
use Concrete\Core\Entity\Board\Instance;
use Doctrine\ORM\EntityManager;

abstract class AbstractUpdateBoardInstanceCommandHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app, EntityManager $entityManager)
    {
        $this->app = $app;
        $this->entityManager = $entityManager;
    }

    protected function getInstances(AbstractUpdateBoardInstanceCommand $command): array
    {
        $dataSource = $this->entityManager->getRepository(DataSource::class)
            ->findOneByHandle($command->getDriver());
        $instances = [];
        if ($dataSource instanceof DataSource) {
            $driver = $dataSource->getDriver();
            $db = $this->entityManager->getConnection();
            $v = [$dataSource->getId(), $driver->getItemPopulator()->getObjectUniqueItemId($command->getObject())];
            $r = $db->executeQuery('select distinct boardInstanceID from BoardInstanceItems bii inner join BoardItems bi on (bii.boardItemID = bi.boardItemID) where bii.configuredDataSourceID = ? and bi.uniqueItemId = ?', $v);
            while ($row = $r->fetchAssociative()) {
                $instances[] = $this->entityManager->find(Instance::class, $row['boardInstanceID']);
            }
        }
        return $instances;
    }

}
