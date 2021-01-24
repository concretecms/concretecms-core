<?php
declare(strict_types=1);

namespace Concrete\Core\Updater\Migrations\Migrations;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Entity\Automation\Task;
use Concrete\Core\Entity\Command\Batch;
use Concrete\Core\Entity\Command\Process;
use Concrete\Core\Entity\Command\TaskProcess;
use Concrete\Core\Job\Job;
use Concrete\Core\Updater\Migrations\AbstractMigration;
use Concrete\Core\Updater\Migrations\RepeatableMigrationInterface;
use Doctrine\DBAL\Schema\Schema;

final class Version20210103123200 extends AbstractMigration implements RepeatableMigrationInterface
{

    public function upgradeDatabase()
    {
        $this->createSinglePage('/dashboard/system/automation', 'Automation');
        $this->createSinglePage('/dashboard/system/notification', 'Notification');
        $this->createSinglePage(
            '/dashboard/system/automation/tasks',
            'Tasks',
            ['meta_keywords' => 'automated jobs, tasks, commands, console, cli']
        );
        $this->createSinglePage(
            '/dashboard/system/automation/activity',
            'Activity',
            ['meta_keywords' => 'processes, queues, jobs, running']
        );
        $this->createSinglePage(
            '/dashboard/system/automation/schedule',
            'Schedule',
            ['meta_keywords' => 'cron, scheduling']
        );
        $this->createSinglePage('/dashboard/system/automation/settings', 'Automation Settings');
        $this->createSinglePage(
            '/dashboard/system/notification/events',
            'Server-Sent Events',
            ['meta_keywords' => 'websocket, socket, socket.io, push, push notifications, mercure']
        );

        $this->refreshEntities(
            [
                Batch::class,
                Process::class,
                TaskProcess::class
            ]
        );

        $this->output(t('Installing automated tasks upgrade XML...'));
        $importer = new ContentImporter();
        $importer->importContentFile(DIR_BASE_CORE . '/config/install/base/tasks.xml');

        $jobsToUninstall = [
            'fill_thumbnails_table',
            'update_gatherings',
            'generate_sitemap'
        ];
        foreach($jobsToUninstall as $jHandle) {
            $job = Job::getByHandle($jHandle);
            if ($job) {
                $job->uninstall();
            }
        }
    }
}