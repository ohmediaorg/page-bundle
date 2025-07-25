<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy;
use OHMedia\PageBundle\Form\Type\AbstractDynamicPageTemplateType;
use OHMedia\PageBundle\Repository\PageRevisionRepository;
use OHMedia\UtilityBundle\Entity\BlameableEntityTrait;
use OHMedia\WysiwygBundle\Shortcodes\Shortcode;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRevisionRepository::class)]
class PageRevision
{
    use BlameableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    #[ORM\Column(length: 255)]
    private ?string $template = null;

    #[ORM\ManyToOne(inversedBy: 'pageRevisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Page $page = null;

    #[ORM\OneToMany(mappedBy: 'pageRevision', targetEntity: PageContentCheckbox::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private Collection $pageContentCheckboxes;

    #[ORM\OneToMany(mappedBy: 'pageRevision', targetEntity: PageContentImage::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private Collection $pageContentImages;

    #[ORM\OneToMany(mappedBy: 'pageRevision', targetEntity: PageContentRow::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private Collection $pageContentRows;

    #[ORM\OneToMany(mappedBy: 'pageRevision', targetEntity: PageContentText::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private Collection $pageContentTexts;

    public function __construct()
    {
        $this->pageContentCheckboxes = new ArrayCollection();
        $this->pageContentImages = new ArrayCollection();
        $this->pageContentRows = new ArrayCollection();
        $this->pageContentTexts = new ArrayCollection();
    }

    public function __toString()
    {
        $dateTime = $this->updated_at ?: new \DateTime();

        $dateTime->setTimeZone(new \DateTimeZone(date_default_timezone_get()));

        return $dateTime->format('M j, Y @ g:ia');
    }

    public function __clone()
    {
        if ($this->id) {
            if ($this instanceof Proxy && !$this->__isInitialized()) {
                // Initialize the proxy to load all properties
                $this->__load();
            }

            $this->id = null;

            $this->published = false;

            $pageContents = $this->getPageContents();

            $this->pageContentCheckboxes = new ArrayCollection();
            $this->pageContentImages = new ArrayCollection();
            $this->pageContentRows = new ArrayCollection();
            $this->pageContentTexts = new ArrayCollection();

            foreach ($pageContents as $pageContent) {
                $clone = clone $pageContent;

                $this->addPageContent($clone);
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplateName()
    {
        $callable = $this->template.'::getTemplateName';

        return is_callable($callable)
            ? call_user_func($callable)
            : $this->template;
    }

    public function isTemplateDynamic(): bool
    {
        return is_subclass_of($this->template, AbstractDynamicPageTemplateType::class);
    }

    public function isTemplate(string $template): bool
    {
        return $this->template === $template;
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

    public function getPageContents(): Collection
    {
        return new ArrayCollection(
            array_merge(
                $this->pageContentCheckboxes->toArray(),
                $this->pageContentImages->toArray(),
                $this->pageContentRows->toArray(),
                $this->pageContentTexts->toArray(),
            ),
        );
    }

    public function addPageContent(AbstractPageContent $pageContent)
    {
        if ($pageContent instanceof PageContentCheckbox) {
            return $this->addPageContentCheckbox($pageContent);
        } elseif ($pageContent instanceof PageContentImage) {
            return $this->addPageContentImage($pageContent);
        } elseif ($pageContent instanceof PageContentRow) {
            return $this->addPageContentRow($pageContent);
        } elseif ($pageContent instanceof PageContentText) {
            return $this->addPageContentText($pageContent);
        }

        return $this;
    }

    public function removePageContent(AbstractPageContent $pageContent)
    {
        if ($pageContent instanceof PageContentCheckbox) {
            return $this->removePageContentCheckbox($pageContent);
        } elseif ($pageContent instanceof PageContentImage) {
            return $this->removePageContentImage($pageContent);
        } elseif ($pageContent instanceof PageContentRow) {
            return $this->removePageContentRow($pageContent);
        } elseif ($pageContent instanceof PageContentText) {
            return $this->removePageContentText($pageContent);
        }

        return $this;
    }

    /**
     * @return Collection<int, PageContentCheckbox>
     */
    public function getPageContentCheckboxes(): Collection
    {
        return $this->pageContentCheckboxes;
    }

    public function addPageContentCheckbox(PageContentCheckbox $pageContentCheckbox): static
    {
        if (!$this->pageContentCheckboxes->contains($pageContentCheckbox)) {
            $this->pageContentCheckboxes->add($pageContentCheckbox);
            $pageContentCheckbox->setPageRevision($this);
        }

        return $this;
    }

    public function removePageContentCheckbox(PageContentCheckbox $pageContentCheckbox): static
    {
        if ($this->pageContentCheckboxes->removeElement($pageContentCheckbox)) {
            // set the owning side to null (unless already changed)
            if ($pageContentCheckbox->getPageRevision() === $this) {
                $pageContentCheckbox->setPageRevision(null);
            }
        }

        return $this;
    }

    public function getPageContentCheckbox(string $name): ?PageContentCheckbox
    {
        foreach ($this->pageContentCheckboxes as $pageContentCheckbox) {
            if ($pageContentCheckbox->getName() === $name) {
                return $pageContentCheckbox;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PageContentImage>
     */
    public function getPageContentImages(): Collection
    {
        return $this->pageContentImages;
    }

    public function addPageContentImage(PageContentImage $pageContentImage): static
    {
        if (!$this->pageContentImages->contains($pageContentImage)) {
            $this->pageContentImages->add($pageContentImage);
            $pageContentImage->setPageRevision($this);
        }

        return $this;
    }

    public function removePageContentImage(PageContentImage $pageContentImage): static
    {
        if ($this->pageContentImages->removeElement($pageContentImage)) {
            // set the owning side to null (unless already changed)
            if ($pageContentImage->getPageRevision() === $this) {
                $pageContentImage->setPageRevision(null);
            }
        }

        return $this;
    }

    public function getPageContentImage(string $name): ?PageContentImage
    {
        foreach ($this->pageContentImages as $pageContentImage) {
            if ($pageContentImage->getName() === $name) {
                return $pageContentImage;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PageContentRow>
     */
    public function getPageContentRows(): Collection
    {
        return $this->pageContentRows;
    }

    public function addPageContentRow(PageContentRow $pageContentRow): static
    {
        if (!$this->pageContentRows->contains($pageContentRow)) {
            $this->pageContentRows->add($pageContentRow);
            $pageContentRow->setPageRevision($this);
        }

        return $this;
    }

    public function removePageContentRow(PageContentRow $pageContentRow): static
    {
        if ($this->pageContentRows->removeElement($pageContentRow)) {
            // set the owning side to null (unless already changed)
            if ($pageContentRow->getPageRevision() === $this) {
                $pageContentRow->setPageRevision(null);
            }
        }

        return $this;
    }

    public function getPageContentRow(string $name): ?PageContentRow
    {
        foreach ($this->pageContentRows as $pageContentRow) {
            if ($pageContentRow->getName() === $name) {
                return $pageContentRow;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PageContentText>
     */
    public function getPageContentTexts(): Collection
    {
        return $this->pageContentTexts;
    }

    public function addPageContentText(PageContentText $pageContentText): static
    {
        if (!$this->pageContentTexts->contains($pageContentText)) {
            $this->pageContentTexts->add($pageContentText);
            $pageContentText->setPageRevision($this);
        }

        return $this;
    }

    public function removePageContentText(PageContentText $pageContentText): static
    {
        if ($this->pageContentTexts->removeElement($pageContentText)) {
            // set the owning side to null (unless already changed)
            if ($pageContentText->getPageRevision() === $this) {
                $pageContentText->setPageRevision(null);
            }
        }

        return $this;
    }

    public function getPageContentText(string $name, string $type): ?PageContentText
    {
        foreach ($this->pageContentTexts as $pageContentText) {
            if ($pageContentText->getName() === $name && $pageContentText->getType() === $type) {
                return $pageContentText;
            }
        }

        return null;
    }

    public function containsShortcode(string $shortcode): bool
    {
        $shortcode = Shortcode::format($shortcode);

        $pageContentTexts = $this->getPageContentTexts();

        foreach ($pageContentTexts as $pageContentText) {
            if (PageContentText::TYPE_WYSIWYG !== $pageContentText->getType()) {
                continue;
            }

            if (str_contains($pageContentText->getText(), $shortcode)) {
                return true;
            }
        }

        $pageContentRows = $this->getPageContentRows();

        foreach ($pageContentRows as $pageContentRow) {
            if (!$pageContentRow->layoutHasOneColumn()) {
                continue;
            }

            if (str_contains($pageContentRow->getColumn1(), $shortcode)) {
                return true;
            }

            if (!$pageContentRow->layoutHasTwoColumns()) {
                continue;
            }

            if (str_contains($pageContentRow->getColumn2(), $shortcode)) {
                return true;
            }

            if (!$pageContentRow->layoutHasThreeColumns()) {
                continue;
            }

            if (str_contains($pageContentRow->getColumn3(), $shortcode)) {
                return true;
            }
        }

        return false;
    }
}
