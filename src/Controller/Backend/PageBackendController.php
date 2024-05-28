<?php

namespace OHMedia\PageBundle\Controller\Backend;

use OHMedia\BackendBundle\Routing\Attribute\Admin;
use OHMedia\BootstrapBundle\Service\Paginator;
use OHMedia\MetaBundle\Entity\Meta;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\PageCreateType;
use OHMedia\PageBundle\Form\PageEditType;
use OHMedia\PageBundle\Form\PageNavigationType;
use OHMedia\PageBundle\Form\PageSEOType;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Security\Voter\PageRevisionVoter;
use OHMedia\PageBundle\Security\Voter\PageVoter;
use OHMedia\PageBundle\Service\PageManager;
use OHMedia\PageBundle\Service\PageRawQuery;
use OHMedia\SecurityBundle\Form\DeleteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Admin]
class PageBackendController extends AbstractController
{
    public const CSRF_TOKEN_REORDER = 'pages_reorder';

    public function __construct(private PageRepository $pageRepository)
    {
    }

    #[Route('/pages', name: 'page_index', methods: ['GET'])]
    public function index(Paginator $paginator): Response
    {
        $this->denyAccessUnlessGranted(
            PageVoter::INDEX,
            new Page(),
            'You cannot access the list of pages.'
        );

        $queryBuilder = $this->pageRepository->createQueryBuilder('p')
            ->orderBy('p.order_global', 'asc');

        return $this->render('@OHMediaPage/page/page_index.html.twig', [
            'pagination' => $paginator->paginate($queryBuilder, 20),
            'new_page' => new Page(),
            'attributes' => $this->getAttributes(),
        ]);
    }

    #[Route('/pages/reorder', name: 'page_reorder', methods: ['GET'])]
    public function reorder()
    {
        $this->denyAccessUnlessGranted(
            PageVoter::REORDER,
            new Page(),
            'You cannot reorder the pages.'
        );

        $homepage = $this->pageRepository->findOneBy([
            'homepage' => true,
        ]);

        $topLevelPages = $this->pageRepository->getTopLevel();
        $csrfTokenName = self::CSRF_TOKEN_REORDER;

        return $this->render('@OHMediaPage/page/page_reorder.html.twig', [
            'top_level_pages' => $topLevelPages,
            'csrf_token_name' => $csrfTokenName,
            'homepage' => $homepage,
        ]);
    }

    #[Route('/pages/reorder', name: 'page_reorder_post', methods: ['POST'])]
    public function reorderPost(
        Request $request,
        PageManager $pageManager,
        PageRawQuery $pageRawQuery
    ): Response {
        $this->denyAccessUnlessGranted(
            PageVoter::REORDER,
            new Page(),
            'You cannot reorder the pages.'
        );

        $csrfToken = $request->request->get(self::CSRF_TOKEN_REORDER);

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_REORDER, $csrfToken)) {
            return new JsonResponse('Invalid CSRF token.', 400);
        }

        $pages = $request->request->all('pages');

        // NOTE: avoiding Doctrine/DQL because updateHierarchy gets called
        // every time a Page is saved and we only need to call it at the end

        $pageRawQuery->beginTransaction();

        try {
            foreach ($pages as $page) {
                $pageRawQuery->update((int) $page['id'], [
                    'id' => $page['id'],
                    'parent_id' => $page['parent_id'] ?: null,
                    'order_local' => $page['order_local'],
                ]);
            }

            $pageRawQuery->commit();
        } catch (\Exception $e) {
            $pageRawQuery->rollBack();

            return new JsonResponse('Data unable to be saved.', 400);
        }

        $pageManager->updateHierarchy();

        return new JsonResponse();
    }

    #[Route('/page/create', name: 'page_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $page = new Page();

        $this->denyAccessUnlessGranted(
            PageVoter::CREATE,
            $page,
            'You cannot create a new page.'
        );

        $form = $this->createForm(PageCreateType::class, $page);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->setPageSlug($page);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // make sure this page is last locally
            // the event subscriber will clean things up
            $page->setOrderLocal(Page::ORDER_LOCAL_END);

            $meta = (new Meta())
                ->setTitle($page->getName())
                ->setAppendBaseTitle(true);

            $page->setMeta($meta);

            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'The page was created successfully.');

            return $this->redirectToView($page);
        }

        return $this->render('@OHMediaPage/page/page_create.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
            'form_title' => 'Create Page',
        ]);
    }

    #[Route('/page/{id}', name: 'page_view', methods: ['GET'])]
    public function view(Page $page): Response
    {
        $this->denyAccessUnlessGranted(
            PageVoter::VIEW,
            $page,
            'You cannot view this page.'
        );

        $currentPageRevision = $page->getCurrentPageRevision();

        if (!$currentPageRevision) {
            // TODO: might have to consider permissions
            return $this->redirectToRoute('page_revision_create', [
                'id' => $page->getId(),
            ]);
        }

        return $this->render('@OHMediaPage/page/page_view.html.twig', [
            'page' => $page,
            'new_page_revision' => (new PageRevision())->setPage($page),
            'current_page_revision' => $currentPageRevision,
            'attributes' => $this->getAttributes(),
        ]);
    }

    #[Route('/page/{id}/edit', name: 'page_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Page $page,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageVoter::EDIT,
            $page,
            'You cannot edit this page.'
        );

        $form = $this->createForm(PageEditType::class, $page);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        $this->setPageSlug($page);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'Changes to the page were saved successfully.');

            return $this->redirectToView($page);
        }

        return $this->render('@OHMediaPage/page/page_edit.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
            'form_title' => 'Edit Page',
        ]);
    }

    #[Route('/page/{id}/navigation', name: 'page_navigation', methods: ['GET', 'POST'])]
    public function navigation(
        Request $request,
        Page $page,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageVoter::NAVIGATION,
            $page,
            'You cannot edit the navigation properties of this page.'
        );

        $form = $this->createForm(PageNavigationType::class, $page);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'The page navigation was updated successfully.');

            return $this->redirectToView($page);
        }

        return $this->render('@OHMediaPage/page/page_navigation.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
            'form_title' => 'Page Navigation',
        ]);
    }

    #[Route('/page/{id}/seo', name: 'page_seo', methods: ['GET', 'POST'])]
    public function seo(
        Request $request,
        Page $page,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageVoter::NAVIGATION,
            $page,
            'You cannot edit the SEO properties of this page.'
        );

        $form = $this->createForm(PageSEOType::class, $page);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'The page SEO was updated successfully.');

            return $this->redirectToView($page);
        }

        return $this->render('@OHMediaPage/page/page_seo.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
            'form_title' => 'Page SEO',
        ]);
    }

    private function setPageSlug(Page $page): void
    {
        $slugger = new AsciiSlugger();

        $slug = $page->getSlug();

        if (!$slug) {
            // create a unique slug
            $name = strtolower($page->getName());

            $slug = $slugger->slug($name);

            $id = $page->getId();

            $i = 1;
            while ($this->pageRepository->countBySlug($slug, $id)) {
                $slug = $slugger->slug($name.'-'.$i);

                ++$i;
            }
        } else {
            // make sure the slug is formatted properly
            $slug = $slugger->slug(strtolower($slug));
        }

        $page->setSlug($slug);
    }

    #[Route('/page/{id}/homepage', name: 'page_homepage', methods: ['POST'])]
    public function homepageAction(
        Request $request,
        Page $page,
    ) {
        $this->denyAccessUnlessGranted(
            PageVoter::HOMEPAGE,
            $page,
            'You cannot make this page the homepage.'
        );

        $csrfTokenName = 'homepage_page_'.$page->getId();
        $csrfTokenValue = $request->request->get($csrfTokenName);

        if ($this->isCsrfTokenValid($csrfTokenName, $csrfTokenValue)) {
            $existingHomepage = $this->pageRepository->findOneBy([
                'homepage' => 1,
            ]);

            $page->setHomepage(true);
            $this->pageRepository->save($page, true);

            if ($existingHomepage && $existingHomepage !== $page) {
                $existingHomepage->setHomepage(false);
                $this->pageRepository->save($existingHomepage, true);
            }

            $this->addFlash('notice', 'The page was set as the homepage.');
        }

        return $this->redirectToView($page);
    }

    #[Route('/page/{id}/publish', name: 'page_publish', methods: ['POST'])]
    public function publishAction(
        Request $request,
        Page $page,
    ) {
        $this->denyAccessUnlessGranted(
            PageVoter::PUBLISH,
            $page,
            'You cannot publish this page.'
        );

        $csrfTokenName = 'publish_page_'.$page->getId();
        $csrfTokenValue = $request->request->get($csrfTokenName);

        if ($this->isCsrfTokenValid($csrfTokenName, $csrfTokenValue)) {
            $page->setPublished(new \DateTimeImmutable());
            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'The page was published.');
        }

        return $this->redirectToView($page);
    }

    #[Route('/page/{id}/unpublish', name: 'page_unpublish', methods: ['POST'])]
    public function unpublishAction(
        Request $request,
        Page $page,
    ) {
        $this->denyAccessUnlessGranted(
            PageVoter::UNPUBLISH,
            $page,
            'You cannot unpublish this page.'
        );

        $csrfTokenName = 'unpublish_page_'.$page->getId();
        $csrfTokenValue = $request->request->get($csrfTokenName);

        if ($this->isCsrfTokenValid($csrfTokenName, $csrfTokenValue)) {
            $page->setPublished(null);
            $this->pageRepository->save($page, true);

            $this->addFlash('notice', 'The page was unpublished.');
        }

        return $this->redirectToView($page);
    }

    #[Route('/page/{id}/delete', name: 'page_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        Page $page,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageVoter::DELETE,
            $page,
            'You cannot delete this page.'
        );

        $form = $this->createForm(DeleteType::class, null);

        $form->add('delete', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->pageRepository->remove($page, true);

            $this->addFlash('notice', 'The page was deleted successfully.');

            return $this->redirectToRoute('page_index');
        }

        $this->addFlash('warning', 'Deleting a page will also delete all descendant pages!');

        return $this->render('@OHMediaPage/page/page_delete.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
            'form_title' => sprintf('Delete Page %s', $page),
        ]);
    }

    private function redirectToView(Page $page): Response
    {
        return $this->redirectToRoute('page_view', [
            'id' => $page->getId(),
        ]);
    }

    private function getAttributes(): array
    {
        $pageAttributes = [
            'create' => PageVoter::CREATE,
            'delete' => PageVoter::DELETE,
            'edit' => PageVoter::EDIT,
            'homepage' => PageVoter::HOMEPAGE,
            'navigation' => PageVoter::NAVIGATION,
            'publish' => PageVoter::PUBLISH,
            'seo' => PageVoter::SEO,
            'reorder' => PageVoter::REORDER,
            'unpublish' => PageVoter::UNPUBLISH,
            'view' => PageVoter::VIEW,
        ];

        $pageRevisionAttributes = [
            'content' => PageRevisionVoter::CONTENT,
            'create' => PageRevisionVoter::CREATE,
            'delete' => PageRevisionVoter::DELETE,
            'template' => PageRevisionVoter::TEMPLATE,
            'publish' => PageRevisionVoter::PUBLISH,
            'view' => PageRevisionVoter::VIEW,
        ];

        return [
            'page' => $pageAttributes,
            'page_revision' => $pageRevisionAttributes,
        ];
    }
}
