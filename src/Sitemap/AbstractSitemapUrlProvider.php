<?php

namespace OHMedia\PageBundle\Sitemap;

abstract class AbstractSitemapUrlProvider
{
    private array $sitemapUrls = [];

    abstract protected function buildSitemapUrls(): void;

    final protected function addSitemapUrl(string $path, \DateTimeInterface $lastmod): static
    {
        $this->sitemapUrls[] = new SitemapUrl($path, $lastmod);

        return $this;
    }

    final public function getSitemapUrls(): array
    {
        $this->buildSitemapUrls();

        return $this->sitemapUrls;
    }
}
