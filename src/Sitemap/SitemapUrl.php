<?php

namespace OHMedia\PageBundle\Sitemap;

class SitemapUrl
{
    public readonly float $priority;

    public function __construct(
        public readonly string $path,
        public readonly \DateTimeInterface $lastmod
    ) {
        $priority = 1.0;

        if ($this->path) {
            $parts = explode('/', $this->path);

            $priority -= 0.1 * count($parts);
        }

        $this->priority = $priority;
    }
}
