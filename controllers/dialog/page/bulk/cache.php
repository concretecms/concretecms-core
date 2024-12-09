<?php

namespace Concrete\Controller\Dialog\Page\Bulk;

use Concrete\Controller\Backend\UserInterface as BackendInterfaceController;
use Concrete\Core\Application\EditResponse;
use Concrete\Core\Command\Batch\Batch;
use Concrete\Core\Page\Command\DeletePageCommand;
use Page;
use Permissions;
use Symfony\Component\HttpFoundation\JsonResponse;

class Cache extends BackendInterfaceController
{
    protected $viewPath = '/dialogs/page/bulk/cache';
    protected $pages;
    protected $canEdit = false;

    protected function canAccess()
    {
        $this->populatePages();

        return $this->canEdit;
    }

    protected function populatePages()
    {
        if (!isset($this->pages)) {
            if (is_array($_REQUEST['item'])) {
                foreach ($_REQUEST['item'] as $cID) {
                    $c = Page::getByID($cID);
                    if (is_object($c) && !$c->isError()) {
                        $this->pages[] = $c;
                    }
                }
            }
        }

        if (count($this->pages) > 0) {
            $this->canEdit = true;
            foreach ($this->pages as $c) {
                $cp = new Permissions($c);
                if (!$cp->canEditPageSpeedSettings()) {
                    $this->canEdit = false;
                }
            }
        } else {
            $this->canEdit = false;
        }

        return $this->canEdit;
    }

    public function view()
    {
        $config = $this->app->make('config');
        $fullPageCaching = -3;
        $cCacheFullPageContentOverrideLifetime = -2;
        $cCacheFullPageContentOverrideLifetimeCustomValue = -1;
        foreach ($this->pages as $c) {
            $cp = new Permissions($c);
            if ($cp->canEditPageSpeedSettings()) {
                if ($c->getCollectionFullPageCaching() != $fullPageCaching && $fullPageCaching != -3) {
                    $fullPageCaching = -2;
                } else {
                    $fullPageCaching = $c->getCollectionFullPageCaching();
                }
                if ($c->getCollectionFullPageCachingLifetime(
                    ) != $cCacheFullPageContentOverrideLifetime && $cCacheFullPageContentOverrideLifetime != -2) {
                    $cCacheFullPageContentOverrideLifetime = -1;
                } else {
                    $cCacheFullPageContentOverrideLifetime = $c->getCollectionFullPageCachingLifetime();
                }
                if ($c->getCollectionFullPageCachingLifetimeCustomValue(
                    ) != $cCacheFullPageContentOverrideLifetimeCustomValue && $cCacheFullPageContentOverrideLifetimeCustomValue != -1) {
                    $cCacheFullPageContentOverrideLifetimeCustomValue = 0;
                } else {
                    $cCacheFullPageContentOverrideLifetimeCustomValue = $c->getCollectionFullPageCachingLifetimeCustomValue();
                }
            }
        }
		switch($config->get('concrete.cache.pages')) {
			case 'blocks':
				$globalSetting = t('cache page if all blocks support it.');
				$enableCache = 1;
				break;
			case 'all':
				$globalSetting = t('enable full page cache.');
				$enableCache = 1;
				break;
            default: // false
				$globalSetting = t('disable full page cache.');
				$enableCache = 0;
				break;
		}
		switch($this->app->make('config')->get('concrete.cache.full_page_lifetime')) {
			case 'custom':
				$custom = $this->app->make('date')->describeInterval($config->get('concrete.cache.full_page_lifetime_value') * 60);
				$globalSettingLifetime = t('%s minutes', $custom);
				break;
			case 'forever':
				$globalSettingLifetime = t('Until manually cleared');
				break;
            default: // "default"
				$globalSettingLifetime = $this->app->make('date')->describeInterval($config->get('concrete.cache.lifetime'));
				break;
		}
        $this->set('pages', $this->pages);
        $this->set('fullPageCaching', $fullPageCaching);
        $this->set('cCacheFullPageContentOverrideLifetime', $cCacheFullPageContentOverrideLifetime);
        $this->set('cCacheFullPageContentOverrideLifetimeCustomValue', $cCacheFullPageContentOverrideLifetimeCustomValue);
        $this->set('globalSetting', $globalSetting);
        $this->set('enableCache', $enableCache);
        $this->set('globalSettingLifetime', $globalSettingLifetime);
    }

    public function submit()
    {
        if ($this->canAccess()) {
            foreach ($this->pages as $page) {
				$data = array();
				if (($cCacheFullPageContent = $this->request->request->getInt('cCacheFullPageContent')) > -2) {
					$data['cCacheFullPageContent'] = $cCacheFullPageContent;
				}
				if ($cCacheFullPageContent === 1) {
                    $data['cCacheFullPageContentOverrideLifetime'] = $this->request->request->get('cCacheFullPageContentOverrideLifetime');
                    if ($data['cCacheFullPageContentOverrideLifetime'] === 'custom') {
                        $data['cCacheFullPageContentLifetimeCustom'] = $this->request->request->getInt('cCacheFullPageContentLifetimeCustom');
                    }
				}
                if (count($data) > 0) {
                    $page->update($data);
                }
            }
            $response = new EditResponse();
            $response->setMessage(t('Cache settings updated successfully.'));
            return new JsonResponse($response);
        } else {
            throw new \RuntimeException(t('Access Denied'));
        }
    }


}
