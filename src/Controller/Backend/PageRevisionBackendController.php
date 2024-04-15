<?php

namespace OHMedia\PageBundle\Controller\Backend;

use OHMedia\BackendBundle\Routing\Attribute\Admin;
use OHMedia\PageBundle\Entity\AbstractPageContent;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\PageRevisionType;
use OHMedia\PageBundle\Repository\PageRevisionRepository;
use OHMedia\PageBundle\Security\Voter\PageRevisionVoter;
use OHMedia\PageBundle\Service\PageRenderer;
use OHMedia\SecurityBundle\Form\DeleteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Admin]
class PageRevisionBackendController extends AbstractController
{
    #[Route('/page/{id}/revision/create', name: 'page_revision_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        Page $page,
        PageRevisionRepository $pageRevisionRepository
    ): Response {
        $pageRevision = new PageRevision();
        $pageRevision->setPage($page);

        $this->denyAccessUnlessGranted(
            PageRevisionVoter::CREATE,
            $pageRevision,
            'You cannot create a new page revision.'
        );

        return $this->form($request, $pageRevision, $pageRevisionRepository);
    }

    #[Route('/page/revision/{id}', name: 'page_revision_view', methods: ['GET'])]
    public function view(PageRenderer $renderer, PageRevision $pageRevision): Response
    {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::VIEW,
            $pageRevision,
            'You cannot view this page revision.'
        );

        $renderer->setCurrentPageRevision($pageRevision);

        return $renderer->renderPage(true);
    }

    #[Route('/page/revision/{id}/template', name: 'page_revision_template', methods: ['GET', 'POST'])]
    public function template(
        Request $request,
        PageRevision $pageRevision,
        PageRevisionRepository $pageRevisionRepository
    ): Response {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::TEMPLATE,
            $pageRevision,
            'You cannot change the template of this page revision.'
        );

        return $this->form($request, $pageRevision, $pageRevisionRepository);
    }

    private function form(
        Request $request,
        PageRevision $pageRevision,
        PageRevisionRepository $pageRevisionRepository
    ): Response {
        $creating = !$pageRevision->getId();

        $oldTemplate = $pageRevision->getTemplate();

        $cloned = false;

        if ($pageRevision->isPublished()) {
            // editing already published content
            // so create a new draft
            $pageRevision = clone $pageRevision;

            $cloned = true;
        }

        $form = $this->createForm(PageRevisionType::class, $pageRevision);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTemplate = $pageRevision->getTemplate();
            $templatesChanged = $newTemplate !== $oldTemplate;

            if ($templatesChanged) {
                $pageRevisionRepository->save($pageRevision, true);

                $this->addFlash('notice', 'The page template was updated successfully.');
            }

            return $this->redirectToParentPage($pageRevision);
        }

        if ($cloned) {
            $this->addFlash('warning', 'Changing the template will create a new page revision.');
        }

        return $this->render('@OHMediaPage/page_revision/page_revision_form.html.twig', [
            'form' => $form->createView(),
            'form_title' => $pageRevision->getId()
                ? 'Change Page Template'
                : 'Select Page Template',
            'page_revision' => $pageRevision,
        ]);
    }

    #[Route('/page/revision/{id}/content', name: 'page_revision_content', methods: ['GET', 'POST'])]
    public function content(
        Request $request,
        PageRevision $pageRevision,
        PageRevisionRepository $pageRevisionRepository
    ) {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::CONTENT,
            $pageRevision,
            'You cannot edit the content of this page revision.'
        );

        $cloned = false;

        if ($pageRevision->isPublished()) {
            // editing already published content
            // so create a new draft
            $pageRevision = clone $pageRevision;

            $cloned = true;
        }

        $form = $this->createForm($pageRevision->getTemplate(), $pageRevision);

        $form->add('submit', SubmitType::class);

        $form->handleRequest($request);

        $isXmlHttpRequest = $request->isXmlHttpRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->all() as $name => $child) {
                $pageContent = $child->getData();

                if (!($pageContent instanceof AbstractPageContent)) {
                    continue;
                }

                $pageRevision->addPageContent($pageContent);
            }

            $pageRevision->setUpdatedAt(new \DateTime());

            $pageRevisionRepository->save($pageRevision, true);

            if ($isXmlHttpRequest) {
                $redirect = $this->generateUrl('page_view', [
                    'id' => $pageRevision->getPage()->getId(),
                    'revision' => $pageRevision->getId(),
                ]);

                return new JsonResponse($redirect);
            }

            $this->addFlash('notice', 'The page revision content was updated.');

            return $this->redirectToParentPage($pageRevision);
        }

        if ($cloned) {
            $this->addFlash('warning', 'Updating the content will create a new page revision.');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            // TODO: don't set a flash message if $isXmlHttpRequest
            // and find a way to pass the error back
            $this->addFlash('error', 'There is an error in the form.');
        }

        if ($isXmlHttpRequest) {
            $renderView = $this->renderView('@OHMediaPage/xhr/form.html.twig', [
                'form' => $form->createView(),
            ]);

            return new JsonResponse($renderView);
        }

        return $this->render('@OHMediaPage/page_revision/page_revision_content.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Edit Page Content',
            'page_revision' => $pageRevision,
        ]);
    }

    #[Route('/page/revision/{id}/publish', name: 'page_revision_publish', methods: ['POST'])]
    public function publishAction(
        Request $request,
        PageRevision $pageRevision,
        PageRevisionRepository $pageRevisionRepository
    ) {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::PUBLISH,
            $pageRevision,
            'You cannot publish this page revision.'
        );

        $csrfTokenName = 'publish_page_revision_'.$pageRevision->getId();
        $csrfTokenValue = $request->request->get($csrfTokenName);

        if ($this->isCsrfTokenValid($csrfTokenName, $csrfTokenValue)) {
            $pageRevision->setPublished(true);
            $pageRevision->setUpdatedAt(new \DateTime());
            $pageRevisionRepository->save($pageRevision, true);

            $this->addFlash('notice', 'The page revision was published.');
        }

        return $this->redirectToParentPage($pageRevision);
    }

    #[Route('/page/revision/{id}/delete', name: 'page_revision_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        PageRevision $pageRevision,
        PageRevisionRepository $pageRevisionRepository
    ): Response {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::DELETE,
            $pageRevision,
            'You cannot delete this page revision.'
        );

        $form = $this->createForm(DeleteType::class, null);

        $form->add('delete', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageRevisionRepository->remove($pageRevision, true);

            $this->addFlash('notice', 'The page revision was deleted successfully.');

            return $this->redirectToParentPage($pageRevision, false);
        }

        return $this->render('@OHMediaPage/page_revision/page_revision_delete.html.twig', [
            'form' => $form->createView(),
            'form_title' => sprintf('Delete Page Revision %s', $pageRevision),
            'page_revision' => $pageRevision,
        ]);
    }

    private function redirectToParentPage(
        PageRevision $pageRevision,
        bool $includeRevision = true
    ): Response {
        $params = [
            'id' => $pageRevision->getPage()->getId(),
        ];

        if ($includeRevision) {
            $params['revision'] = $pageRevision->getId();
        }

        return $this->redirectToRoute('page_view', $params);
    }
}
