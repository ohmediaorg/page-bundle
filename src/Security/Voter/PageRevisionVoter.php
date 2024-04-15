<?php

namespace OHMedia\PageBundle\Security\Voter;

use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Security\Voter\AbstractEntityVoter;

class PageRevisionVoter extends AbstractEntityVoter
{
    public const INDEX = 'index';
    public const CREATE = 'create';
    public const VIEW = 'view';
    public const TEMPLATE = 'template';
    public const DELETE = 'delete';
    public const CONTENT = 'content';
    public const PUBLISH = 'publish';

    protected function getAttributes(): array
    {
        return [
            self::INDEX,
            self::CREATE,
            self::VIEW,
            self::TEMPLATE,
            self::DELETE,
            self::CONTENT,
            self::PUBLISH,
        ];
    }

    protected function getEntityClass(): string
    {
        return PageRevision::class;
    }

    protected function canIndex(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }

    protected function canCreate(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }

    protected function canView(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }

    protected function canTemplate(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }

    protected function canDelete(PageRevision $pageRevision, User $loggedIn): bool
    {
        if (!$pageRevision->isPublished()) {
            // can always delete draft revisions
            return true;
        }

        $page = $pageRevision->getPage();

        if ($pageRevision === $page->getCurrentPageRevision()) {
            // can't delete the live revision
            return false;
        }

        // can delete any other published revision
        return true;
    }

    protected function canContent(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }

    protected function canPublish(PageRevision $pageRevision, User $loggedIn): bool
    {
        return true;
    }
}
