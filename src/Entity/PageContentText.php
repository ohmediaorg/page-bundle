<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OHMedia\PageBundle\Repository\PageContentTextRepository;

#[ORM\Entity(repositoryClass: PageContentTextRepository::class)]
class PageContentText extends AbstractPageContent
{
    public const TYPE_CHOICE = 'choice';
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_WYSIWYG = 'wysiwyg';

    #[ORM\ManyToOne(inversedBy: 'pageContentTexts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(length: 10)]
    private string $type;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
