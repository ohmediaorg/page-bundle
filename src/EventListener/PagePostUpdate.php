<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostUpdate
{
    public function __construct(private PageManager $pageManager)
    {
    }

    public function postUpdate(Page $page, PostUpdateEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
