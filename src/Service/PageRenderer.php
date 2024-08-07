<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BootstrapBundle\Component\Breadcrumb;
use OHMedia\MetaBundle\Entity\Meta;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Event\DynamicPageEvent;
use OHMedia\PageBundle\Repository\PageRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class PageRenderer
{
    private ?Page $currentPage = null;
    private ?PageRevision $currentPageRevision = null;
    private array $dynamicBreadcrumbs = [];
    private ?Meta $dynamicMeta = null;
    private ?string $dynamicPart = null;

    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $eventDispatcher,
        private PageRepository $pageRepository,
    ) {
    }

    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    public function getCurrentPageRevision(): ?PageRevision
    {
        return $this->currentPageRevision;
    }

    public function getDynamicBreadcrumbs(): array
    {
        return $this->dynamicBreadcrumbs;
    }

    public function addDynamicBreadcrumb(string $text, string $path): self
    {
        array_unshift($this->dynamicBreadcrumbs, new Breadcrumb(
            $text,
            'oh_media_page_frontend',
            ['path' => $path]
        ));

        return $this;
    }

    public function getDynamicPart(): ?string
    {
        return $this->dynamicPart;
    }

    public function getMetaEntity(): Meta
    {
        if ($this->dynamicMeta) {
            return $this->dynamicMeta;
        }

        if ($this->currentPage) {
            return $this->currentPage->getMeta();
        }

        return (new Meta())
            ->setTitle('Page not found')
            ->getAppendBaseTitle(true);
    }

    public function setDynamicMeta(?Meta $dynamicMeta): self
    {
        $this->dynamicMeta = $dynamicMeta;

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

        $this->setCurrentPage($dynamicPage);

        if ($dynamicPage) {
            $this->dynamicPart = implode('/', $dynamicParts);

            $this->eventDispatcher->dispatch(new DynamicPageEvent());
        }

        return $this;
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
