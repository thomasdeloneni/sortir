<?php

namespace App\Controller;

use App\Form\SortieFilterType;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Core\User\UserInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(Request $request,SortieRepository $sortieRepository, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire de filtre
        $form = $this->createForm(SortieFilterType::class);
        $form->handleRequest($request);

        $queryBuilder = $entityManager->getRepository(Sortie::class)->createQueryBuilder('s');

        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            if ($filters['nom']) {
                $queryBuilder->andWhere('s.nom LIKE :nom')
                             ->setParameter('nom', '%' . $filters['nom'] . '%');
            }

            if ($filters['startDate']) {
                $queryBuilder->andWhere('s.dateHeureDebut >= :startDate')
                    ->setParameter('startDate', $filters['startDate']);
            }

            if ($filters['endDate']) {
                $queryBuilder->andWhere('s.dateHeureDebut <= :endDate')
                    ->setParameter('endDate', $filters['endDate']);
            }

            if ($filters['campus']) {
                $queryBuilder->andWhere('s.campus = :campus')
                             ->setParameter('campus', $filters['campus']);
            }
        }

        $sorties = $queryBuilder->getQuery()->getResult();

        return $this->render('main/home.html.twig', [
            'form' => $form->createView(),
            'sorties' => $sorties,
        ]);
    }
}

