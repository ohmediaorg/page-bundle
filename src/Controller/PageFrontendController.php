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

        // manual redirects supercede page paths
        $redirectPath = $this->getRedirectPath(
            $entityPathManager,
            $redirectRepository,
            $path,
            true,
        );

        if ($redirectPath) {
            return $this->redirect($redirectPath, 302);
        }

        $pageRenderer->setCurrentPageFromPath($path);

        $page = $pageRenderer->getCurrentPage();

        if ($path && $page && $page->isHomepage()) {
            // we are on the literal page that is flagged as homepage
            // may as well redirect to the actual homepage
            return $this->redirectToRoute('oh_media_page_frontend', [
                'path' => '',
            ], 302);
        }

        if (!$page) {
            // no page found, check for a system redirect
            $redirectPath = $this->getRedirectPath(
                $entityPathManager,
                $redirectRepository,
                $path,
                false,
            );

            if ($redirectPath) {
                return $this->redirect($redirectPath, 302);
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
            ], 302);
        }

        if ($page->isRedirectTypeInternal()) {
            $path = $entityPathManager->getEntityPath(
                $page->getRedirectInternal(),
            );

            if ($path) {
                return $this->redirect($path, 302);
            }
        } elseif ($page->isRedirectTypeExternal()) {
            return $this->redirect($page->getRedirectExternal(), 302);
        }

        return $pageRenderer->renderPage();
    }

    private function getRedirectPath(
        EntityPathManager $entityPathManager,
        RedirectRepository $redirectRepository,
        string $path,
        bool $manual,
    ): ?string {
        $redirect = $redirectRepository->findOneBy([
            'path' => $path,
            'manual' => $manual,
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
                'manual' => $manual,
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
