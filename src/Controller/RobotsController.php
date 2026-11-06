<?php

namespace OHMedia\PageBundle\Controller;

use OHMedia\PageBundle\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RobotsController extends AbstractController
{
    #[Route('/robots.txt', name: 'oh_media_page_robots')]
    public function robots(PageRepository $pageRepository): Response
    {
        $pages = $pageRepository->createQueryBuilder('p')
            ->where('p.noindex = 1')
            ->getQuery()
            ->getResult();

        $noindex = [];

        foreach ($pages as $page) {
            if (!$page->isVisibleToPublic()) {
                continue;
            }

            $noindex[] = $page;
        }

        $response = $this->render('@OHMediaPage/robots.txt.twig', [
            'noindex' => $noindex,
        ]);

        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
}
