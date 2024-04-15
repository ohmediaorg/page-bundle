<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BackendBundle\Service\AbstractNavItemProvider;
use OHMedia\BootstrapBundle\Component\Nav\NavItemInterface;
use OHMedia\BootstrapBundle\Component\Nav\NavLink;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Security\Voter\PageVoter;

class PageNavItemProvider extends AbstractNavItemProvider
{
    public function getNavItem(): ?NavItemInterface
    {
        if ($this->isGranted(PageVoter::INDEX, new Page())) {
            return (new NavLink('Pages', 'page_index'))
                ->setIcon('files');
        }

        return null;
    }
}
