<?php

namespace Concrete\Core\Command\Process;

use Concrete\Core\Command\Batch\Batch as PendingBatch;
use Concrete\Core\Command\Batch\BatchUpdater;
use Concrete\Core\Entity\Command\Batch;
use Concrete\Core\Entity\Command\Process;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Messenger\MessageBusInterface;
use Concrete\Core\Command\Batch\Command\HandleBatchMessageCommand;

class ProcessFactory
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Date
     */
    protected $dateService;

    /**
     * @var MessageBusInterface
     */
    protected $messageBus;

    /**
     * @var BatchUpdater
     */
    protected $batchUpdater;

    public function __construct(
        EntityManager $entityManager,
        Date $dateService,
        MessageBusInterface $messageBus,
        BatchUpdater $batchUpdater
    ) {
        $this->entityManager = $entityManager;
        $this->dateService = $dateService;
        $this->messageBus = $messageBus;
        $this->batchUpdater = $batchUpdater;
    }

    public function createProcess(string $name): Process
    {
        $process = new Process();
        // First, create the underlying batch object
        $process = new Process();
        $process->setDateStarted($this->dateService->toDateTime()->getTimestamp());
        $process->setName($name);

        $user = new User();
        if ($user) {
            $userInfo = $user->getUserInfoObject();
            if ($userInfo) {
                $process->setUser($userInfo->getEntityObject());
            }
        }

        $this->entityManager->persist($process);
        $this->entityManager->flush();
        return $process;
    }

    /**
     * @param PendingBatch $batch
     */
    public function createWithBatch(PendingBatch $batch): Process
    {
        $process = $this->createProcess($batch->getName());

        $batchEntity = new Batch();
        $this->entityManager->persist($batchEntity);
        $this->entityManager->flush();

        $totalJobs = 0;
        foreach ($batch->getMessages() as $message) {
            $command = new HandleBatchMessageCommand($batchEntity->getID(), $message);
            $this->messageBus->dispatch($command);
            $totalJobs++;
        }
        $this->batchUpdater->updateJobs($batchEntity->getID(), BatchUpdater::COLUMN_TOTAL, $totalJobs);
        $this->batchUpdater->updateJobs($batchEntity->getID(), BatchUpdater::COLUMN_PENDING, $totalJobs);
        $this->entityManager->refresh($batchEntity);

        $process->setBatch($batchEntity);
        $this->entityManager->persist($process);
        $this->entityManager->flush();

        return $process;
    }


}