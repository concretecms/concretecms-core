<?php
defined('C5_EXECUTE') or die("Access Denied.");
$c = Page::getCurrentPage();
?>

<div class="list-group">
    <a
        class="list-group-item <?=($action === 'details') ? ' active' : ''?>"
        href="<?=URL::to('/dashboard/boards/details', $board->getBoardID())?>"
    >
        <?=t('Details')?>
    </a>
    <a
            class="list-group-item <?=($action === 'data_sources') ? ' active' : ''?>"
            href="<?=URL::to('/dashboard/boards/data_sources', $board->getBoardID())?>"
    >
        <?=t('Data Sources')?>
    </a>
    <a
            class="list-group-item <?=($action === 'weighting') ? ' active' : ''?>"
            href="<?=URL::to('/dashboard/boards/weighting', $board->getBoardID())?>"
    >
        <?=t('Weighting')?>
    </a>
    <a
            class="list-group-item <?=($action === 'pool') ? ' active' : ''?>"
            href="<?=URL::to('/dashboard/boards/pool', $board->getBoardID())?>"
    >
        <?=t('Data Pool')?>
    </a>
    <a
            class="list-group-item <?=($action === 'instances') ? ' active' : ''?>"
            href="<?=URL::to('/dashboard/boards/instances', $board->getBoardID())?>"
    >
        <?=t('Board Instances')?>
    </a>
</div>