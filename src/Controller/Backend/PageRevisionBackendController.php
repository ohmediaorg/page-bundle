<?php

namespace OHMedia\PageBundle\Controller\Backend;

use Doctrine\ORM\EntityManagerInterface;
use OHMedia\BackendBundle\Routing\Attribute\Admin;
use OHMedia\PageBundle\Entity\AbstractPageContent;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\PageRevisionType;
use OHMedia\PageBundle\Repository\PageRevisionRepository;
use OHMedia\PageBundle\Security\Voter\PageRevisionVoter;
use OHMedia\PageBundle\Service\PageRawQuery;
use OHMedia\PageBundle\Service\PageRenderer;
use OHMedia\UtilityBundle\Form\DeleteType;
use OHMedia\WysiwygBundle\Shortcodes\ShortcodeManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Admin]
class PageRevisionBackendController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageRevisionRepository $pageRevisionRepository
    ) {
    }

    #[Route('/page/{id}/revision/create', name: 'page_revision_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        #[MapEntity(id: 'id')] Page $page,
    ): Response {
        $pageRevision = new PageRevision();
        $pageRevision->setPage($page);

        $this->denyAccessUnlessGranted(
            PageRevisionVoter::CREATE,
            $pageRevision,
            'You cannot create a new page revision.'
        );

        return $this->form($request, $pageRevision);
    }

    #[Route('/page/revision/{id}', name: 'page_revision_view', methods: ['GET'])]
    public function view(
        PageRenderer $renderer,
        #[MapEntity(id: 'id')] PageRevision $pageRevision,
    ): Response {
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
        #[MapEntity(id: 'id')] PageRevision $pageRevision,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::TEMPLATE,
            $pageRevision,
            'You cannot change the template of this page revision.'
        );

        return $this->form($request, $pageRevision);
    }

    private function form(
        Request $request,
        PageRevision $pageRevision,
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

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $newTemplate = $pageRevision->getTemplate();
                $templatesChanged = $newTemplate !== $oldTemplate;

                if ($templatesChanged) {
                    $this->pageRevisionRepository->save($pageRevision, true);

                    $this->addFlash('notice', 'The page template was updated successfully.');
                }

                return $this->redirectToParentPage($pageRevision);
            }

            $this->addFlash('error', 'There are some errors in the form below.');
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
        #[MapEntity(id: 'id')] PageRevision $pageRevision,
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

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                foreach ($form->all() as $name => $child) {
                    $pageContent = $child->getData();

                    if (!($pageContent instanceof AbstractPageContent)) {
                        continue;
                    }

                    $pageRevision->addPageContent($pageContent);
                }

                $pageRevision->setUpdatedAt(new \DateTime());

                $this->pageRevisionRepository->save($pageRevision, true);

                $this->addFlash('notice', 'The page revision content was updated.');

                return $this->redirectToParentPage($pageRevision);
            }

            $this->addFlash('error', 'There are some errors in the form below.');
        }

        if ($cloned) {
            $this->addFlash('warning', 'Updating the content will create a new page revision.');
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
        PageRawQuery $pageRawQuery,
        #[MapEntity(id: 'id')] PageRevision $pageRevision,
        ShortcodeManager $shortcodeManager,
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

            $this->purgePageContent($pageRevision);

            $this->pageRevisionRepository->save($pageRevision, true);

            $isDynamic = $this->isDynamic($pageRevision, $shortcodeManager);

            $pageRawQuery->update($pageRevision->getPage()->getId(), [
                'dynamic' => $isDynamic,
            ]);

            $this->addFlash('notice', 'The page revision was published.');
        }

        return $this->redirectToParentPage($pageRevision);
    }

    /**
     * Removes AbstractPageContent entities that are not relevant to the current template.
     */
    private function purgePageContent(PageRevision $pageRevision): void
    {
        $contentForm = $this->createForm($pageRevision->getTemplate(), $pageRevision);

        $pageContents = $pageRevision->getPageContents();

        foreach ($pageContents as $pageContent) {
            $name = $pageContent->getName();

            if ($contentForm->has($name)) {
                $dataClass = $contentForm->get($name)->getConfig()->getDataClass();

                if ($dataClass === $pageContent::class) {
                    continue;
                }
            }

            $pageRevision->removePageContent($pageContent);

            $this->entityManager->remove($pageContent);
        }
    }

    private function isDynamic(PageRevision $pageRevision, ShortcodeManager $shortcodeManager)
    {
        if ($pageRevision->getPage()->isHomepage()) {
            return false;
        }

        if ($pageRevision->isTemplateDynamic()) {
            return true;
        }

        $dynamicShortcodes = $shortcodeManager->getDynamicShortcodes();

        if (!$dynamicShortcodes) {
            return false;
        }

        foreach ($dynamicShortcodes as $dynamicShortcode) {
            if ($pageRevision->containsShortcode($dynamicShortcode)) {
                return true;
            }
        }

        return false;
    }

    #[Route('/page/revision/{id}/delete', name: 'page_revision_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id')] PageRevision $pageRevision,
    ): Response {
        $this->denyAccessUnlessGranted(
            PageRevisionVoter::DELETE,
            $pageRevision,
            'You cannot delete this page revision.'
        );

        $form = $this->createForm(DeleteType::class, null);

        $form->add('delete', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->pageRevisionRepository->remove($pageRevision, true);

                $this->addFlash('notice', 'The page revision was deleted successfully.');

                return $this->redirectToParentPage($pageRevision, false);
            }

            $this->addFlash('error', 'There are some errors in the form below.');
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
