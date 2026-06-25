<?php

namespace OHMedia\PageBundle\Controller\Backend;

use Doctrine\ORM\QueryBuilder;
use OHMedia\BackendBundle\Form\MultiSaveType;
use OHMedia\BackendBundle\Routing\Attribute\Admin;
use OHMedia\BootstrapBundle\Service\Paginator;
use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\PageBundle\Form\RedirectType;
use OHMedia\PageBundle\Repository\RedirectRepository;
use OHMedia\PageBundle\Security\Voter\RedirectVoter;
use OHMedia\UtilityBundle\Form\DeleteType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Admin]
class RedirectController extends AbstractController
{
    public function __construct(private RedirectRepository $redirectRepository)
    {
    }

    #[Route('/redirects', name: 'redirect_index', methods: ['GET'])]
    public function index(
        Paginator $paginator,
        Request $request,
    ): Response {
        $newRedirect = new Redirect();

        $this->denyAccessUnlessGranted(
            RedirectVoter::INDEX,
            $newRedirect,
            'You cannot access the list of redirects.'
        );

        $qb = $this->redirectRepository->createQueryBuilder('r');
        $qb->orderBy('r.updated_at', 'desc');

        $searchForm = $this->getSearchForm($request);

        $this->applySearch($searchForm, $qb);

        return $this->render('@OHMediaPage/redirect/redirect_index.html.twig', [
            'pagination' => $paginator->paginate($qb, 20),
            'new_redirect' => $newRedirect,
            'attributes' => $this->getAttributes(),
            'search_form' => $searchForm,
        ]);
    }

    private function getSearchForm(Request $request): FormInterface
    {
        $formBuilder = $this->container->get('form.factory')
            ->createNamedBuilder('', FormType::class, null, [
                'csrf_protection' => false,
            ]);

        $formBuilder->setMethod('GET');

        $formBuilder->add('search', SearchType::class, [
            'required' => false,
            'label' => 'Path',
        ]);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        return $form;
    }

    private function applySearch(FormInterface $form, QueryBuilder $qb): void
    {
        $search = $form->get('search')->getData();

        if ($search) {
            $searchFields = [
                'r.path',
            ];

            $searchLikes = [];
            foreach ($searchFields as $searchField) {
                $searchLikes[] = "$searchField LIKE :search";
            }

            $qb->andWhere('('.implode(' OR ', $searchLikes).')')
                ->setParameter('search', '%'.$search.'%');
        }
    }

    #[Route('/redirect/create', name: 'redirect_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $redirect = new Redirect();

        $this->denyAccessUnlessGranted(
            RedirectVoter::CREATE,
            $redirect,
            'You cannot create a new redirect.'
        );

        $form = $this->createForm(RedirectType::class, $redirect);

        $form->add('save', MultiSaveType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->redirectRepository->save($redirect, true);

                $this->addFlash('notice', 'The redirect was created successfully.');

                return $this->redirectForm($redirect, $form);
            }

            $this->addFlash('error', 'There are some errors in the form below.');
        }

        return $this->render('@OHMediaPage/redirect/redirect_create.html.twig', [
            'form' => $form->createView(),
            'redirect' => $redirect,
        ]);
    }

    #[Route('/redirect/{id}/edit', name: 'redirect_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id')] Redirect $redirect,
    ): Response {
        $this->denyAccessUnlessGranted(
            RedirectVoter::EDIT,
            $redirect,
            'You cannot edit this redirect.'
        );

        $form = $this->createForm(RedirectType::class, $redirect);

        $form->add('save', MultiSaveType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->redirectRepository->save($redirect, true);

                $this->addFlash('notice', 'The redirect was updated successfully.');

                return $this->redirectForm($redirect, $form);
            }

            $this->addFlash('error', 'There are some errors in the form below.');
        }

        return $this->render('@OHMediaPage/redirect/redirect_edit.html.twig', [
            'form' => $form->createView(),
            'redirect' => $redirect,
        ]);
    }

    private function redirectForm(Redirect $redirect, FormInterface $form): Response
    {
        $clickedButtonName = $form->getClickedButton()->getName() ?? null;

        if ('keep_editing' === $clickedButtonName) {
            return $this->redirectToRoute('redirect_edit', [
                'id' => $redirect->getId(),
            ]);
        } elseif ('add_another' === $clickedButtonName) {
            return $this->redirectToRoute('redirect_create');
        }

        return $this->redirectToRoute('redirect_index');
    }

    #[Route('/redirect/{id}/delete', name: 'redirect_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id')] Redirect $redirect,
    ): Response {
        $this->denyAccessUnlessGranted(
            RedirectVoter::DELETE,
            $redirect,
            'You cannot delete this redirect.'
        );

        $form = $this->createForm(DeleteType::class, null);

        $form->add('delete', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->redirectRepository->remove($redirect, true);

                $this->addFlash('notice', 'The redirect was deleted successfully.');

                return $this->redirectToRoute('redirect_index');
            }

            $this->addFlash('error', 'There are some errors in the form below.');
        }

        return $this->render('@OHMediaPage/redirect/redirect_delete.html.twig', [
            'form' => $form->createView(),
            'redirect' => $redirect,
        ]);
    }

    private function getAttributes(): array
    {
        return [
            'create' => RedirectVoter::CREATE,
            'delete' => RedirectVoter::DELETE,
            'edit' => RedirectVoter::EDIT,
        ];
    }
}
