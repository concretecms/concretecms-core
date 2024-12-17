<?php

namespace Concrete\Core\Page\Command;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Attribute\Category\PageCategory;
use Concrete\Core\Board\Command\UpdateBoardInstancesLinkedToObjectCommand;
use Concrete\Core\Board\DataSource\Driver\PageDriver;
use Concrete\Core\Cache\Page\PageCache;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Summary\Template\Populator;
use Concrete\Core\Search\Index\IndexManagerInterface;
use Concrete\Core\Board\DataSource\Driver\Manager as BoardDataSourceManager;

class ReindexPageCommandHandler implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var PageCategory
     */
    protected $pageCategory;

    /**
     * @var IndexManagerInterface
     */
    protected $indexManager;

    /**
     * @var Populator
     */
    protected $populator;

    /**
     * @var BoardDataSourceManager
     */
    protected $boardDataSourceManager;

    public function __construct(
        BoardDataSourceManager $boardDataSourceManager,
        Populator $populator,
        PageCategory $pageCategory,
        IndexManagerInterface $indexManager
    ) {
        $this->boardDataSourceManager = $boardDataSourceManager;
        $this->populator = $populator;
        $this->pageCategory = $pageCategory;
        $this->indexManager = $indexManager;
    }

    public function __invoke($command)
    {
        $c = Page::getByID($command->getPageID(), 'ACTIVE');
        if ($c && !$c->isError()) {
            // reindex page attributes
            $indexer = $this->pageCategory->getSearchIndexer();
            $values = $this->pageCategory->getAttributeValues($c);
            foreach ($values as $value) {
                $indexer->indexEntry($this->pageCategory, $value, $c);
            }

            // clear page cache
            $cache = PageCache::getLibrary();
            $cache->purge($c);

            // Populate summary templates
            $this->populator->updateAvailableSummaryTemplates($c);

            // Reindex page content.
            $this->indexManager->index(Page::class, $command->getPageID());

            // Rebuild any boards that might pertain to this
            $command = new UpdateBoardInstancesLinkedToObjectCommand('page', $c);
            $this->app->executeCommand($command);
        }
    }


}