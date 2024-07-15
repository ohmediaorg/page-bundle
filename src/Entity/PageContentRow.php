<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OHMedia\PageBundle\Repository\PageContentRowRepository;

#[ORM\Entity(repositoryClass: PageContentRowRepository::class)]
class PageContentRow extends AbstractPageContent
{
    public const LAYOUT_ONE_COLUMN = 'one_column';
    public const LAYOUT_TWO_COLUMN = 'two_column';
    public const LAYOUT_THREE_COLUMN = 'three_column';
    public const LAYOUT_SIDEBAR_LEFT = 'sidebar_left';
    public const LAYOUT_SIDEBAR_RIGHT = 'sidebar_right';

    #[ORM\ManyToOne(inversedBy: 'pageContentRows')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $layout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $column_1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $column_2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $column_3 = null;

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function layoutHasOneColumn()
    {
        return !empty($this->layout);
    }

    public function layoutHasTwoColumns()
    {
        return in_array($this->layout, [
            self::LAYOUT_TWO_COLUMN,
            self::LAYOUT_THREE_COLUMN,
            self::LAYOUT_SIDEBAR_LEFT,
            self::LAYOUT_SIDEBAR_RIGHT,
        ]);
    }

    public function layoutHasThreeColumns()
    {
        return self::LAYOUT_THREE_COLUMN === $this->layout;
    }

    public function getColumn1(): ?string
    {
        return $this->column_1;
    }

    public function setColumn1(?string $column_1): self
    {
        $this->column_1 = $column_1;

        return $this;
    }

    public function getColumn2(): ?string
    {
        return $this->column_2;
    }

    public function setColumn2(?string $column_2): self
    {
        $this->column_2 = $column_2;

        return $this;
    }

    public function getColumn3(): ?string
    {
        return $this->column_3;
    }

    public function setColumn3(?string $column_3): self
    {
        $this->column_3 = $column_3;

        return $this;
    }
}
