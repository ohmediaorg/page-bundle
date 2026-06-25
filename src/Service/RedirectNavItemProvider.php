<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BackendBundle\Service\AbstractNavItemProvider;
use OHMedia\BootstrapBundle\Component\Nav\NavItemInterface;
use OHMedia\BootstrapBundle\Component\Nav\NavLink;
use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\PageBundle\Security\Voter\RedirectVoter;

class RedirectNavItemProvider extends AbstractNavItemProvider
{
    public function getNavItem(): ?NavItemInterface
    {
        if ($this->isGranted(RedirectVoter::INDEX, new Redirect())) {
            return (new NavLink('Redirects', 'redirect_index'))
                ->setIcon('arrow-up-right-square');
        }

        return null;
    }
}
