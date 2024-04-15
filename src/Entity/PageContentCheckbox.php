<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OHMedia\PageBundle\Repository\PageContentCheckboxRepository;

#[ORM\Entity(repositoryClass: PageContentCheckboxRepository::class)]
class PageContentCheckbox extends AbstractPageContent
{
    #[ORM\ManyToOne(inversedBy: 'pageContentCheckboxes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $checked = false;

    public function getChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): self
    {
        $this->checked = $checked;

        return $this;
    }
}
