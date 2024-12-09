<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy;
use OHMedia\UtilityBundle\Entity\BlameableEntityTrait;

#[ORM\MappedSuperclass]
abstract class AbstractPageContent
{
    use BlameableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    protected ?string $name = null;

    public function __clone()
    {
        if ($this->id) {
            if ($this instanceof Proxy && !$this->__isInitialized()) {
                // Initialize the proxy to load all properties
                $this->__load();
            }

            $this->id = null;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPageRevision(): ?PageRevision
    {
        return $this->pageRevision;
    }

    public function setPageRevision(?PageRevision $pageRevision): static
    {
        $this->pageRevision = $pageRevision;

        return $this;
    }
}
