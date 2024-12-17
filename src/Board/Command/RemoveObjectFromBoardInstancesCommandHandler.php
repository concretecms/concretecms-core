<?php

namespace Concrete\Core\Board\Command;

class RemoveObjectFromBoardInstancesCommandHandler extends AbstractUpdateBoardInstanceCommandHandler
{
    public function __invoke(RemoveObjectFromBoardInstancesCommand $command): void
    {
        foreach ($this->getInstances($command) as $instance) {
            $regenerateCommand = new RegenerateBoardInstanceCommand();
            $regenerateCommand->setDefer(true);
            $regenerateCommand->setInstance($instance);
            $this->app->executeCommand($regenerateCommand);
        }
    }
    

}
