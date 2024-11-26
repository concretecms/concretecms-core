<?php

declare(strict_types=1);

namespace Concrete\Core\Updater\Migrations\Migrations;

use Concrete\Core\Application\UserInterface\Dashboard\Navigation\NavigationCache;
use Concrete\Core\Page\Page;
use Concrete\Core\Updater\Migrations\AbstractMigration;
use Concrete\Core\Updater\Migrations\RepeatableMigrationInterface;

final class Version20241126174022 extends AbstractMigration implements RepeatableMigrationInterface
{
    public function upgradeDatabase()
    {
        $page = Page::getByPath('/dashboard/system/environment/debug');
        if ($page && !$page->isError()) {
            $page->delete();
        }
        $this->createSinglePage('/dashboard/system/environment/errors', 'Error Handling',
            [
                'meta_keywords' => 'error, exception, debug',
            ]
        );
        $navigationCache = $this->app->make(NavigationCache::class);
        $navigationCache->clear();
    }
}
