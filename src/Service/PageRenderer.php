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
    private bool $preview = false;

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
            $homepage = $this->pageRepository->findOneBy([
                'homepage' => true,
            ]);

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

        $this->preview = $preview;

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

    public function isPreview(): bool
    {
        return $this->preview;
    }

    private function getPagePreviewScript()
    {
        ob_start(); ?>
<style>
  [data-ohmedia-page-content] {
    cursor: copy;
    border: 2px dashed #fe5b15;
    min-height: 10px;
  }

  span[data-ohmedia-page-content] {
    display: inline-block;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const links = document.querySelectorAll('a[href]');

  links.forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();

      return false;
    });
  });

  function doesRectContain(r, x, y) {
    return r.top <= y && r.bottom >= y && r.left <= x && r.right >= x;
  }

  const previewEls = document.querySelectorAll('[data-ohmedia-page-content]');

  let pageContentAvailable = [];

  previewEls.forEach(function(previewEl) {
    pageContentAvailable.push(previewEl.dataset.ohmediaPageContent);
  });

  parent.postMessage({
    pageContentAvailable: pageContentAvailable,
  }, '*');

  document.addEventListener('click', function(e) {
    let pageContent = [];

    previewEls.forEach(function(previewEl) {
      const rect = previewEl.getBoundingClientRect();

      if (doesRectContain(rect, e.clientX, e.clientY)) {
        pageContent.push(previewEl.dataset.ohmediaPageContent);
      }
    });

    if (pageContent.length) {
      parent.postMessage({
        pageContent: pageContent,
      }, '*');
    }
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
