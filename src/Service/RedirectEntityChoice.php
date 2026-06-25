<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\SecurityBundle\Service\EntityChoiceInterface;

class RedirectEntityChoice implements EntityChoiceInterface
{
    public function getLabel(): string
    {
        return 'Redirects';
    }

    public function getEntities(): array
    {
        return [Redirect::class];
    }
}
