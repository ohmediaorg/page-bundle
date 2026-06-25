<?php

namespace OHMedia\PageBundle\Security\Voter;

use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Security\Voter\AbstractEntityVoter;

class RedirectVoter extends AbstractEntityVoter
{
    public const INDEX = 'index';
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function getAttributes(): array
    {
        return [
            self::INDEX,
            self::CREATE,
            self::EDIT,
            self::DELETE,
        ];
    }

    protected function getEntityClass(): string
    {
        return Redirect::class;
    }

    protected function canIndex(Redirect $redirect, User $loggedIn): bool
    {
        return true;
    }

    protected function canCreate(Redirect $redirect, User $loggedIn): bool
    {
        return true;
    }

    protected function canEdit(Redirect $redirect, User $loggedIn): bool
    {
        return true;
    }

    protected function canDelete(Redirect $redirect, User $loggedIn): bool
    {
        return true;
    }
}
