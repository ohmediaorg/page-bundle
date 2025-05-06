<?php

namespace OHMedia\PageBundle\Security\Voter;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Security\Voter\AbstractEntityVoter;

class PageVoter extends AbstractEntityVoter
{
    public const INDEX = 'index';
    public const REORDER = 'reorder';
    public const CREATE = 'create';
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const NAVIGATION = 'navigation';
    public const SEO = 'seo';
    public const HOMEPAGE = 'homepage';
    public const PUBLISH = 'publish';
    public const UNPUBLISH = 'unpublish';
    public const DELETE = 'delete';

    protected function getAttributes(): array
    {
        return [
            self::INDEX,
            self::REORDER,
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::NAVIGATION,
            self::SEO,
            self::HOMEPAGE,
            self::PUBLISH,
            self::UNPUBLISH,
            self::DELETE,
        ];
    }

    protected function getEntityClass(): string
    {
        return Page::class;
    }

    protected function canIndex(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canReorder(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canCreate(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canView(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canEdit(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canNavigation(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canSeo(Page $page, User $loggedIn): bool
    {
        return true;
    }

    protected function canHomepage(Page $page, User $loggedIn): bool
    {
        if ($page->isHomepage()) {
            return false;
        }

        if ($page->isDynamic()) {
            return false;
        }

        if (!$page->isVisibleToPublic()) {
            return false;
        }

        if ($page->getParent()) {
            return false;
        }

        if ($page->getPages()->count()) {
            return false;
        }

        return true;
    }

    protected function canPublish(Page $page, User $loggedIn): bool
    {
        return !$page->getPublished();
    }

    protected function canUnpublish(Page $page, User $loggedIn): bool
    {
        return $page->getPublished() && !$page->isHomepage();
    }

    protected function canDelete(Page $page, User $loggedIn): bool
    {
        return !$page->isHomepage() && !$page->isPublished();
    }
}
