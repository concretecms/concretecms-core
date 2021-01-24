<?php
namespace Concrete\Controller\SinglePage\Dashboard\System\Automation;

use Concrete\Core\Command\Process\Command\DeleteProcessCommand;
use Concrete\Core\Command\Process\Logger\LoggerFactoryInterface;
use Concrete\Core\Entity\Command\Process;
use Concrete\Core\Notification\Mercure\MercureService;
use Concrete\Core\Page\Controller\DashboardPageController;
use Symfony\Component\HttpFoundation\JsonResponse;

class Activity extends DashboardPageController
{

    public function view($processID = null)
    {
        $r = $this->entityManager->getRepository(Process::class);
        $mercureService = $this->app->make(MercureService::class);
        $eventSource = null;
        if ($mercureService->isEnabled()) {
            $eventSource = $mercureService->getPublisherUrl();
        }
        $consumeMethod = $this->app->make('config')->get('concrete.messenger.consume.method');
        $this->set('consume', $consumeMethod === 'app' ? true : false);
        $this->set('consumeToken', $this->token->generate('consume_messages'));
        $this->set('processes', $r->findBy([], ['dateCompleted' => 'desc']));
        $this->set('processID', $processID);
        $this->set('eventSource', $eventSource);
    }


    public function delete($token = null)
    {
        $process = $this->entityManager->find(
            Process::class,
            $this->request->request->get('processId')
        );
        if (!$this->token->validate('delete', $token)) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$process) {
            $this->error->add(t('Invalid process ID'));
        }
        if (!$this->error->has()) {
            $this->executeCommand(new DeleteProcessCommand($process->getID()));
            return new JsonResponse($process);
        }

        return new JsonResponse($this->error);
    }

    public function details($token = null)
    {
        $process = $this->entityManager->find(
            Process::class,
            $this->request->request->get('processId')
        );
        if (!$this->token->validate('details', $token)) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$process) {
            $this->error->add(t('Invalid process ID'));
        }
        if (!$this->error->has()) {
            $logger = $this->app->make(LoggerFactoryInterface::class)->createFromProcess($process);
            if ($logger) {
                if ($logger->logExists()) {
                    return new JsonResponse($logger->readAsArray());
                }
            }
        }

        return new JsonResponse([]);
    }


}