<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostPersist
{
    public function __construct(private PageManager $pageManager)
    {
    }

    public function postPersist(Page $page, PostPersistEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
