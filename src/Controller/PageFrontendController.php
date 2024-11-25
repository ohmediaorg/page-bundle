<?php

namespace OHMedia\PageBundle\Controller;

use OHMedia\PageBundle\Repository\Page301Repository;
use OHMedia\PageBundle\Service\PageRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PageFrontendController extends AbstractController
{
    #[Route('/{path}', name: 'oh_media_page_frontend', requirements: ['path' => '.*'], priority: PHP_INT_MIN)]
    public function frontend(
        Page301Repository $page301Repository,
        PageRenderer $pageRenderer,
        string $path
    ) {
        $pageRenderer->setCurrentPageFromPath($path);

        $page = $pageRenderer->getCurrentPage();

        if ($path && $page && $page->isHomepage()) {
            // we are on the literal page that is flagged as homepage
            // may as well redirect to the actual homepage
            return $this->redirectToRoute('oh_media_page_frontend', [
                'path' => '',
            ], 301);
        }

        if (!$page) {
            $page301Path = $this->getPage301Path($page301Repository, $path);

            if ($page301Path) {
                return $this->redirectToRoute('oh_media_page_frontend', [
                    'path' => $page301Path,
                ], 301);
            }
        }

        if (!$page || !$page->isPublished()) {
            throw $this->createNotFoundException('Page not found.');
        }

        if ($page->isLocked()) {
            $this->denyAccessUnlessGranted(
                'IS_AUTHENTICATED_FULLY',
                null,
                'You must log in to view this page.'
            );
        }

        if ($page->isRedirectTypeInternal()) {
            return $this->redirectToRoute('oh_media_page_frontend', [
                'path' => $page->getRedirectInternal()->getPath(),
            ], 301);
        } elseif ($page->isRedirectTypeExternal()) {
            return $this->redirect($page->getRedirectExternal(), 301);
        }

        return $pageRenderer->renderPage();
    }

    private function getPage301Path(
        Page301Repository $page301Repository,
        string $path
    ): ?string {
        $page301 = $page301Repository->findOneBy([
            'path' => $path,
        ], [
            'id' => 'DESC',
        ]);

        if ($page301) {
            return $page301->getPage()->getPath();
        }

        // try finding a dynamic page
        $parts = explode('/', $path);
        $dynamicParts = [];
        $dynamicPage301 = null;

        while ($parts && !$dynamicPage301) {
            $dynamicPart = array_pop($parts);
            $dynamicPath = implode('/', $parts);
            array_unshift($dynamicParts, $dynamicPart);

            $dynamicPage301 = $page301Repository->findOneBy([
                'path' => $dynamicPath,
            ], [
                'id' => 'DESC',
            ]);
        }

        if (!$dynamicPage301) {
            return null;
        }

        $dynamicPage = $dynamicPage301->getPage();

        if ($dynamicPage && $dynamicPage->isDynamic()) {
            return $dynamicPage->getPath().'/'.implode('/', $dynamicParts);
        }

        return null;
    }
}
