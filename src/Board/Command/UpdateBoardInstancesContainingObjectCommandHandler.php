<?php

namespace Concrete\Core\Board\Command;

class UpdateBoardInstancesContainingObjectCommandHandler extends AbstractUpdateBoardInstanceCommandHandler
{
    public function __invoke(UpdateBoardInstancesContainingObjectCommand $command): void
    {
        foreach ($this->getInstances($command) as $instance) {
            $refreshCommand = new RefreshBoardInstanceCommand();
            $refreshCommand->setInstance($instance);
            $this->app->executeCommand($refreshCommand);
        }
    }
    

}
