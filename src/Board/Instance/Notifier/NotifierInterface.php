<?php

namespace Concrete\Core\Board\Instance\Notifier;

defined('C5_EXECUTE') or die("Access Denied.");

interface NotifierInterface
{
    public function findBoardInstancesThatMayContainObject($object): array;
}

