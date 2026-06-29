<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BackendBundle\Service\AbstractDeveloperOnlyNavLinkProvider;
use OHMedia\BootstrapBundle\Component\Nav\NavLink;
use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\PageBundle\Security\Voter\RedirectVoter;

class RedirectsNavLinkProvider extends AbstractDeveloperOnlyNavLinkProvider
{
    public function getNavLink(): NavLink
    {
        return (new NavLink('Redirects', 'redirect_index'))
            ->setIcon('arrow-up-right-square');
    }

    public function getVoterAttribute(): string
    {
        return RedirectVoter::INDEX;
    }

    public function getVoterSubject(): mixed
    {
        return new Redirect();
    }
}
