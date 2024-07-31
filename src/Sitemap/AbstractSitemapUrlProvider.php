<?php

namespace OHMedia\PageBundle\Sitemap;

abstract class AbstractSitemapUrlProvider
{
    private bool $built = false;
    private array $sitemapUrls = [];

    abstract protected function buildSitemapUrls(): void;

    final protected function addSitemapUrl(string $path, \DateTimeInterface $lastmod): static
    {
        $this->sitemapUrls[] = new SitemapUrl($path, $lastmod);

        return $this;
    }

    final public function getSitemapUrls(): array
    {
        if (!$this->built) {
            $this->built = true;

            $this->buildSitemapUrls();
        }

        return $this->sitemapUrls;
    }
}
