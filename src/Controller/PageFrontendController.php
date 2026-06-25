<?php

namespace OHMedia\PageBundle\Controller;

use OHMedia\PageBundle\Repository\RedirectRepository;
use OHMedia\PageBundle\Security\Voter\PageLockedVoter;
use OHMedia\PageBundle\Service\PageRenderer;
use OHMedia\UtilityBundle\Service\EntityPathManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PageFrontendController extends AbstractController
{
    #[Route('/{path}', name: 'oh_media_page_frontend', requirements: ['path' => '.*'], priority: PHP_INT_MIN)]
    public function frontend(
        EntityPathManager $entityPathManager,
        RedirectRepository $redirectRepository,
        PageRenderer $pageRenderer,
        string $path,
    ) {
        $path = rtrim($path, '/');

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
            $redirectPath = $this->getRedirectPath(
                $entityPathManager,
                $redirectRepository,
                $path,
            );

            if ($redirectPath) {
                return $this->redirect($redirectPath, 301);
            }
        }

        if (!$page || !$page->isPublished()) {
            throw $this->createNotFoundException('Page not found.');
        }

        $this->denyAccessUnlessGranted(
            PageLockedVoter::LOCKED,
            $page,
            'You must log in to view this page.'
        );

        if ($redirectPage = $page->getDropdownOnlyRedirect()) {
            return $this->redirectToRoute('oh_media_page_frontend', [
                'path' => $redirectPage->getPath(),
            ], 301);
        }

        if ($page->isRedirectTypeInternal()) {
            $path = $entityPathManager->getEntityPath(
                $page->getRedirectInternal(),
            );

            if ($path) {
                return $this->redirect($path, 301);
            }
        } elseif ($page->isRedirectTypeExternal()) {
            return $this->redirect($page->getRedirectExternal(), 301);
        }

        return $pageRenderer->renderPage();
    }

    private function getRedirectPath(
        EntityPathManager $entityPathManager,
        RedirectRepository $redirectRepository,
        string $path,
    ): ?string {
        $redirect = $redirectRepository->findOneBy([
            'path' => $path,
        ], [
            'updated_at' => 'DESC',
        ]);

        if ($redirect) {
            $redirectPath = $entityPathManager->getEntityPath(
                $redirect->getEntity(),
            );

            if ($redirectPath) {
                return $redirectPath;
            }
        }

        // try finding a dynamic page
        $parts = explode('/', $path);
        $dynamicParts = [];
        $dynamicRedirect = null;

        while ($parts && !$dynamicRedirect) {
            $dynamicPart = array_pop($parts);
            $dynamicPath = implode('/', $parts);
            array_unshift($dynamicParts, $dynamicPart);

            $dynamicRedirect = $redirectRepository->findOneBy([
                'path' => $dynamicPath,
            ], [
                'updated_at' => 'DESC',
            ]);
        }

        if (!$dynamicRedirect) {
            return null;
        }

        $redirectPath = $entityPathManager->getEntityPath(
            $dynamicRedirect->getEntity(),
        );

        if ($redirectPath) {
            return $redirectPath;
        }

        return null;
    }
}
