<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostRemove
{
    private $pageManager;

    public function __construct(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function postRemove(Page $page, PostRemoveEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
