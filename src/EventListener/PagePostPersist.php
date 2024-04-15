<?php

namespace OHMedia\PageBundle\EventListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageManager;

class PagePostPersist
{
    private $pageManager;

    public function __construct(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function postPersist(Page $page, PostPersistEventArgs $event)
    {
        $this->pageManager->updateHierarchy();
    }
}
