<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OHMedia\FileBundle\Entity\File;
use OHMedia\PageBundle\Repository\PageContentImageRepository;

#[ORM\Entity(repositoryClass: PageContentImageRepository::class)]
class PageContentImage extends AbstractPageContent
{
    #[ORM\ManyToOne(inversedBy: 'pageContentImages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?File $image = null;

    public function __clone()
    {
        $this->id = null;

        if ($this->image) {
            $this->image = clone $this->image;
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
