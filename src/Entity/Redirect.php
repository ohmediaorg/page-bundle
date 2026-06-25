<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OHMedia\PageBundle\Repository\RedirectRepository;
use OHMedia\UtilityBundle\Entity\BlameableEntityTrait;

// use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RedirectRepository::class)]
class Redirect
{
    use BlameableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __toString(): string
    {
        return 'Redirect #'.$this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
