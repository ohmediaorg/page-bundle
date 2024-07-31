<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use OHMedia\MetaBundle\Entity\Meta;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\TimezoneBundle\Util\DateTimeUtil;
use OHMedia\UtilityBundle\Entity\BlameableEntityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

// TODO: see if we just want unique slugs regardless of parent
// otherwise we need to account for this when reordering pages

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[UniqueEntity(
    fields: ['parent_slug'],
    errorPath: 'slug',
    message: 'This slug is already in use with the associated Parent Page.',
)]
#[ORM\HasLifecycleCallbacks]
class Page
{
    use BlameableEntityTrait;

    public const ORDER_LOCAL_END = 9999;

    public const REDIRECT_TYPE_NONE = '';
    public const REDIRECT_TYPE_INTERNAL = 'internal';
    public const REDIRECT_TYPE_EXTERNAL = 'external';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true])]
    private ?int $order_local = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    private ?int $order_global = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'pages', cascade: ['remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['order_local' => 'ASC'])]
    private Collection $pages;

    #[ORM\Column(options: ['default' => false])]
    private bool $new_window = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $hidden = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $locked = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $published = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    private ?int $nesting_level = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $parent_slug = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $homepage = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $noindex = false;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $canonical = null;

    #[ORM\OneToMany(mappedBy: 'page', targetEntity: Page301::class, cascade: ['remove'])]
    private Collection $page301s;

    #[ORM\OneToMany(mappedBy: 'page', targetEntity: PageRevision::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['updated_at' => 'DESC'])]
    private Collection $pageRevisions;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Meta $meta = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $redirect_type = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $redirect_internal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $redirect_external = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nav_text = null;

    #[ORM\Column(nullable: true)]
    private ?bool $dynamic = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $sitemap = true;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->page301s = new ArrayCollection();
        $this->pageRevisions = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
        $this->name = null;
        $this->slug = null;
        $this->order_local = self::ORDER_LOCAL_END;
        $this->order_global = null;
        $this->parent = null;
        $this->pages = new ArrayCollection();
        $this->published = null;
        $this->nesting_level = null;
        $this->path = null;
        $this->parent_slug = null;
        $this->homepage = false;
        $this->page301s = new ArrayCollection();
        $this->redirect_type = null;
        $this->redirect_internal = null;
        $this->redirect_external = null;

        $currentPageRevision = $this->getCurrentPageRevision();

        $this->pageRevisions = new ArrayCollection();

        if ($currentPageRevision) {
            $cloned = clone $currentPageRevision;

            $this->addPageRevision($cloned);
        }

        $this->meta = null;
    }

    public function __toString(): string
    {
        return $this->name;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        $this->setParentSlug();

        return $this;
    }

    public function getOrderLocal(): ?int
    {
        return $this->order_local;
    }

    public function setOrderLocal(?int $order_local): static
    {
        $this->order_local = $order_local;

        return $this;
    }

    public function getOrderGlobal(): ?int
    {
        return $this->order_global;
    }

    public function setOrderGlobal(?int $order_global): static
    {
        $this->order_global = $order_global;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        $this->setParentSlug();

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(self $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->setParent($this);
        }

        return $this;
    }

    public function removePage(self $page): static
    {
        if ($this->pages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getParent() === $this) {
                $page->setParent(null);
            }
        }

        return $this;
    }

    public function isNewWindow(): bool
    {
        return $this->new_window;
    }

    public function setNewWindow(bool $new_window): static
    {
        $this->new_window = $new_window;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function isLocked(): bool
    {
        if ($this->isHomepage()) {
            // homepage cannot be locked
            return false;
        }

        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getPublished(): ?\DateTimeImmutable
    {
        if (!$this->published && $this->isHomepage()) {
            return new \DateTimeImmutable();
        }

        return $this->published;
    }

    public function setPublished(?\DateTimeImmutable $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function isPublished(): bool
    {
        if (!$this->getPublished()) {
            return false;
        }

        if (DateTimeUtil::isFuture($this->getPublished())) {
            return false;
        }

        return $this->isCurrentPageRevisionPublished();
    }

    public function getNestingLevel(): ?int
    {
        return $this->nesting_level;
    }

    public function setNestingLevel(int $nesting_level): static
    {
        $this->nesting_level = $nesting_level;

        return $this;
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

    public function getParentSlug(): string
    {
        $parentId = $this->parent ? $this->parent->getId() : 0;

        return $parentId.':'.$this->slug;
    }

    public function setParentSlug(string $parent_slug = null): static
    {
        $this->parent_slug = $this->getParentSlug();

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(PrePersistEventArgs $eventArgs)
    {
        $this->setParentSlug();
    }

    #[ORM\PreUpdate]
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->setParentSlug();
    }

    public function isHomepage(): bool
    {
        return $this->homepage;
    }

    public function setHomepage(bool $homepage): static
    {
        $this->homepage = $homepage;

        if ($homepage) {
            // make sure certain properties have the correct values
            // this should already be handled via the PageVoter
            $this
                // homepage must be indexable
                ->setNoIndex(false)
                // homepage must be canonical to itself
                ->setCanonical(null)
                // homepage cannot be locked
                ->setLocked(false)
                // homepage cannot be dynamic
                ->setDynamic(false)
            ;

            // permissions shouldn't allow a page with child pages
            // to become the homepage, but this is here as a failsafe
            // so those pages don't get excluded from navigation
            foreach ($this->getPages() as $page) {
                $page->setParent($this->getParent());
            }

            $this->setParent(null);
        }

        return $this;
    }

    public function isNoindex(): bool
    {
        if ($this->isHomepage()) {
            return false;
        }

        return $this->noindex;
    }

    public function setNoindex(bool $noindex): static
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function getCanonical(): ?self
    {
        if ($this->isHomepage()) {
            return null;
        }

        return $this->canonical;
    }

    public function setCanonical(?self $canonical): static
    {
        $this->canonical = $canonical;

        return $this;
    }

    /**
     * @return Collection<int, Page301>
     */
    public function getPage301s(): Collection
    {
        return $this->page301s;
    }

    public function addPage301(Page301 $page301): static
    {
        if (!$this->page301s->contains($page301)) {
            $this->page301s->add($page301);
            $page301->setPage($this);
        }

        return $this;
    }

    public function removePage301(Page301 $page301): static
    {
        if ($this->page301s->removeElement($page301)) {
            // set the owning side to null (unless already changed)
            if ($page301->getPage() === $this) {
                $page301->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PageRevision>
     */
    public function getPageRevisions(): Collection
    {
        return $this->pageRevisions;
    }

    public function getPublishedPageRevisions(): Collection
    {
        return $this->pageRevisions->filter(function (PageRevision $pageRevision) {
            return $pageRevision->isPublished();
        });
    }

    public function getCurrentPageRevision(bool $publishedOnly = false): ?PageRevision
    {
        $firstPublished = $this->getPublishedPageRevisions()->first();

        if ($firstPublished) {
            return $firstPublished;
        }

        if ($publishedOnly) {
            return null;
        }

        $first = $this->pageRevisions->first();

        return $first ?: null;
    }

    public function isCurrentPageRevisionPublished(): bool
    {
        $currentPageRevision = $this->getCurrentPageRevision();

        return $currentPageRevision && $currentPageRevision->isPublished();
    }

    public function getTemplateName(): string
    {
        $currentPageRevision = $this->getCurrentPageRevision();

        return $currentPageRevision ? $currentPageRevision->getTemplateName() : '';
    }

    public function addPageRevision(PageRevision $pageRevision): static
    {
        if (!$this->pageRevisions->contains($pageRevision)) {
            $this->pageRevisions->add($pageRevision);
            $pageRevision->setPage($this);
        }

        return $this;
    }

    public function removePageRevision(PageRevision $pageRevision): static
    {
        if ($this->pageRevisions->removeElement($pageRevision)) {
            // set the owning side to null (unless already changed)
            if ($pageRevision->getPage() === $this) {
                $pageRevision->setPage(null);
            }
        }

        return $this;
    }

    public function getMeta(): ?Meta
    {
        return $this->meta;
    }

    public function setMeta(?Meta $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function getRedirectType(): ?string
    {
        return $this->redirect_type;
    }

    public function setRedirectType(?string $redirectType): static
    {
        $this->redirect_type = $redirectType;

        return $this;
    }

    public function isRedirectTypeInternal(): bool
    {
        return self::REDIRECT_TYPE_INTERNAL === $this->redirect_type;
    }

    public function isRedirectTypeExternal(): bool
    {
        return self::REDIRECT_TYPE_EXTERNAL === $this->redirect_type;
    }

    public function getRedirectInternal(): ?self
    {
        return $this->redirect_internal;
    }

    public function setRedirectInternal(?self $redirectInternal): static
    {
        $this->redirect_internal = $redirectInternal;

        return $this;
    }

    public function getRedirectExternal(): ?string
    {
        return $this->redirect_external;
    }

    public function setRedirectExternal(?string $redirectExternal): static
    {
        $this->redirect_external = $redirectExternal;

        return $this;
    }

    public function isVisibleToPublic()
    {
        return $this->isPublished()
            && !$this->isLocked();
    }

    public function isNavEligible()
    {
        return $this->isPublished()
            && !$this->isHidden();
    }

    public function getNavPages(): Collection
    {
        return $this->pages->filter(function (Page $page) {
            return $page->isNavEligible();
        });
    }

    public function getNavText(): ?string
    {
        return $this->nav_text;
    }

    public function setNavText(?string $nav_text): static
    {
        $this->nav_text = $nav_text;

        return $this;
    }

    public function isDynamic(): ?bool
    {
        if ($this->isHomepage()) {
            // homepage cannot be dynamic
            return false;
        }

        return $this->dynamic;
    }

    public function setDynamic(?bool $dynamic): static
    {
        $this->dynamic = $dynamic;

        return $this;
    }

    public function isSitemap(): ?bool
    {
        return $this->sitemap;
    }

    public function setSitemap(bool $sitemap): static
    {
        $this->sitemap = $sitemap;

        return $this;
    }
}
