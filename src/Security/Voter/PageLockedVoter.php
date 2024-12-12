<?php

namespace OHMedia\PageBundle\Security\Voter;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\SecurityBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PageLockedVoter extends Voter
{
    public const LOCKED = 'locked';

    public function supportsAttribute(string $attribute): bool
    {
        return self::LOCKED === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Page::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        return $subject instanceof Page;
    }

    protected function voteOnAttribute(string $attribute, mixed $page, TokenInterface $token): bool
    {
        if (!$page->isLocked()) {
            return true;
        }

        $loggedIn = $token->getUser();

        if (!$loggedIn instanceof User) {
            return false;
        }

        if (!$loggedIn->isEnabled()) {
            return false;
        }

        if (
            $loggedIn->isTypeDeveloper()
            || $loggedIn->isTypeSuper()
            || $loggedIn->isTypeAdmin()
        ) {
            return true;
        }

        $lockedUserTypes = $page->getLockedUserTypes();

        if (null === $lockedUserTypes) {
            return true;
        }

        return in_array($loggedIn->getType(), $lockedUserTypes);
    }
}
