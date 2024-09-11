<?php

namespace App\Controller;

use App\Form\model\SortieSearch;
use App\Form\SortieFilterType;
use App\Repository\SortieRepository;
use App\Service\UpdateStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    public function __construct(private UpdateStatusService $updateStatusService)
    {
    }

    /**
     * @throws \Exception
     */
    #[Route('/', name: 'app_main')]
    public function index(Request $request, SortieRepository $sortieRepository, PaginatorInterface $paginator): Response
    {
        $this->updateStatusService->updateStatus();
        $search = new SortieSearch();
        $form = $this->createForm(SortieFilterType::class, $search);
        $form->handleRequest($request);

        $queryBuilder = $sortieRepository->findByFilters($search);

        if ($form->isSubmitted() && $form->isValid()) {
            $queryBuilder = $sortieRepository->findByFilters($search);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('main/home.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }
}

