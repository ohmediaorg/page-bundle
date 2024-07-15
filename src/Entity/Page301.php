<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OHMedia\PageBundle\Repository\Page301Repository;
use OHMedia\UtilityBundle\Entity\BlameableEntityTrait;

#[ORM\Entity(repositoryClass: Page301Repository::class)]
class Page301
{
    use BlameableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\ManyToOne(inversedBy: 'page301s')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Page $page = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }
}
