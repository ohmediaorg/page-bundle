<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostUpdate
{
    private $pageManager;

    public function __construct(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function postUpdate(Page $page, PostUpdateEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
