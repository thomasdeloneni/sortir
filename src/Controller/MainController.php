<?php

namespace App\Controller;

use App\Form\model\SortieSearch;
use App\Form\SortieFilterType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(Request $request,SortieRepository $sortieRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $search = new SortieSearch();
        $form = $this->createForm(SortieFilterType::class, $search);
        $form->handleRequest($request);

        $sorties = $sortieRepository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $sorties = $sortieRepository->findByFilters($search);
        }

        return $this->render('main/home.html.twig', [
            'form' => $form->createView(),
            'sorties' => $sorties,
        ]);
    }
}

