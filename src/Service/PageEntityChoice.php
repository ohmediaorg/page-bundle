<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\SecurityBundle\Service\EntityChoiceInterface;

class PageEntityChoice implements EntityChoiceInterface
{
    public function getLabel(): string
    {
        return 'Pages';
    }

    public function getEntities(): array
    {
        return [Page::class, PageRevision::class];
    }
}
