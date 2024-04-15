<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OHMedia\SecurityBundle\Entity\Traits\BlameableTrait;

#[ORM\MappedSuperclass]
abstract class AbstractPageContent
{
    use BlameableTrait;

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(length: 255)]
    protected ?string $name = null;

    public function __clone()
    {
        $this->id = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPageRevision(): ?PageRevision
    {
        return $this->pageRevision;
    }

    public function setPageRevision(?PageRevision $pageRevision): self
    {
        $this->pageRevision = $pageRevision;

        return $this;
    }
}
