<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\MetaBundle\Entity\Meta;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Repository\PageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class PageRenderer
{
    private ?Page $currentPage = null;
    private ?PageRevision $currentPageRevision = null;
    private ?string $dynamicPart = null;
    private ?Meta $metaEntity = null;

    public function __construct(
        private Environment $twig,
        private PageRepository $pageRepository)
    {
    }

    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    public function getCurrentPageRevision(): ?PageRevision
    {
        return $this->currentPageRevision;
    }

    public function getDynamicPart(): ?string
    {
        return $this->dynamicPart;
    }

    public function getMetaEntity(): Meta
    {
        if ($this->metaEntity) {
            return $this->metaEntity;
        }

        if ($this->currentPage) {
            return $this->currentPage->getMeta();
        }

        return (new Meta())
            ->setTitle('Page not found')
            ->getAppendBaseTitle(true);
    }

    public function setMetaEntity(?Meta $metaEntity): self
    {
        $this->metaEntity = $metaEntity;

        return $this;
    }

    public function getCanonicalPath(): ?string
    {
        if ($this->currentPage->isHomepage()) {
            return '';
        }

        if ($this->currentPage->isDynamic() && $this->dynamicPart) {
            return $this->currentPage->getPath().'/'.$this->dynamicPart;
        }

        $canonicalPage = $this->currentPage->getCanonical();

        if ($canonicalPage && $canonicalPage->isVisibleToPublic()) {
            return $canonicalPage->getPath();
        }

        return $this->currentPage->getPath();
    }

    public function setCurrentPageRevision(PageRevision $pageRevision): self
    {
        $this->currentPageRevision = $pageRevision;
        $this->currentPage = $pageRevision->getPage();

        return $this;
    }

    public function setCurrentPage(?Page $currentPage): self
    {
        $this->currentPage = $currentPage;
        $this->currentPageRevision = $currentPage
            ? $currentPage->getCurrentPageRevision(true)
            : null;

        return $this;
    }

    public function setCurrentPageFromPath(string $path): self
    {
        $path = ltrim($path, '/');

        if (!$path) {
            $homepage = $this->pageRepository->getHomepage();

            return $this->setCurrentPage($homepage);
        }

        $page = $this->pageRepository->findOneBy([
            'path' => $path,
        ]);

        if ($page) {
            return $this->setCurrentPage($page);
        }

        // try finding a dynamic page
        $parts = explode('/', $path);
        $dynamicParts = [];
        $dynamicPage = null;

        while ($parts && !$dynamicPage) {
            $dynamicPart = array_pop($parts);
            $dynamicPath = implode('/', $parts);
            array_unshift($dynamicParts, $dynamicPart);

            $dynamicPage = $this->pageRepository->findOneBy([
                'path' => $dynamicPath,
                'dynamic' => true,
            ]);
        }

        if ($dynamicPage) {
            $this->dynamicPart = implode('/', $dynamicParts);
        }

        return $this->setCurrentPage($dynamicPage);
    }

    public function renderPage(bool $preview = false): Response
    {
        if (!$this->currentPage || !$this->currentPageRevision) {
            throw new NotFoundHttpException('Page not found.');
        }

        $template = $this->currentPageRevision->getTemplate();

        $twigTemplate = call_user_func($template.'::getTemplate');

        $rendered = $this->twig->render($twigTemplate, [
            'page' => $this->currentPage,
        ]);

        if ($preview) {
            $script = $this->getPagePreviewScript();

            $rendered = str_replace('</body>', $script.'</body>', $rendered);
        }

        return new Response($rendered);
    }

    private function getPagePreviewScript()
    {
        ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const links = document.querySelectorAll('a[href]');

  links.forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();

      return false;
    });
  });

  function postBodyHeight() {
    parent.postMessage({
      scrollHeight: document.body.scrollHeight,
    }, '*');
  }

  const resizeObserver = new ResizeObserver(postBodyHeight);

  resizeObserver.observe(document.body);

  postBodyHeight();
});
</script> <?php
        return ob_get_clean();
    }
}
