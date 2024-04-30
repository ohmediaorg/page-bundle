<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostRemove
{
    public function __construct(private PageManager $pageManager)
    {
    }

    public function postRemove(Page $page, PostRemoveEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
