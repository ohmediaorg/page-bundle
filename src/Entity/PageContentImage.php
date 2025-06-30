<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy;
use OHMedia\FileBundle\Entity\File;
use OHMedia\PageBundle\Repository\PageContentImageRepository;

#[ORM\Entity(repositoryClass: PageContentImageRepository::class)]
class PageContentImage extends AbstractPageContent
{
    #[ORM\ManyToOne(inversedBy: 'pageContentImages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private ?File $image = null;

    public function __clone()
    {
        if ($this->id) {
            if ($this instanceof Proxy && !$this->__isInitialized()) {
                // Initialize the proxy to load all properties
                $this->__load();
            }

            $this->id = null;

            if ($this->image) {
                $this->image = clone $this->image;
            }
        }
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    public function setImage(?File $image): self
    {
        $this->image = $image;

        return $this;
    }
}
