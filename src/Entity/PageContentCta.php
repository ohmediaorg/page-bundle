<?php

namespace OHMedia\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy;
use OHMedia\PageBundle\Repository\PageContentCtaRepository;
use OHMedia\UtilityBundle\Entity\CallToAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageContentCtaRepository::class)]
class PageContentCta extends AbstractPageContent
{
    #[ORM\ManyToOne(inversedBy: 'pageContentCtas')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?PageRevision $pageRevision = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private ?CallToAction $cta = null;

    public function __clone()
    {
        if ($this->id) {
            if ($this instanceof Proxy && !$this->__isInitialized()) {
                // Initialize the proxy to load all properties
                $this->__load();
            }

            $this->id = null;

            if ($this->cta) {
                $this->cta = clone $this->cta;
            }
        }
    }

    public function getCta(): ?CallToAction
    {
        return $this->cta;
    }

    public function setCta(?CallToAction $cta): self
    {
        $this->cta = $cta;

        return $this;
    }
}
