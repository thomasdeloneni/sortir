<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Form\SortieCancel;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie')]
final class SortieController extends AbstractController
{

    #[Route('/new', name: 'app_sortie_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $userEnCours = $this->getUser();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($form->get('Enregistrer')->isClicked()) {
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                    $sortie->setEtat($etat);
                } elseif ($form->get('Publier')->isClicked()) {
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                    $sortie->setEtat($etat);
                }
                $sortie->setOrganisateur($userEnCours);
                $entityManager->persist($sortie);
                $entityManager->flush();
                return $this->redirectToRoute('app_main');
            }
        }
        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
            'userEnCours' => $userEnCours,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_show')]
    public function sortieDetail(Sortie $sortie): Response
    {
        $lieu = $sortie->getLieu();
        $ville = $lieu->getVille();
        $participants = $sortie->getParticipant();
        $organisateur = $sortie->getOrganisateur();

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'ville' => $ville,
            'participants' => $participants,
            'organisateur' => $organisateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sortie_edit')]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($form->get('Enregistrer')->isClicked()) {
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                    $sortie->setEtat($etat);
                } elseif ($form->get('Publier')->isClicked()) {
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                    $sortie->setEtat($etat);
                }
                $entityManager->persist($sortie);
                $entityManager->flush();

                return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
            }
        }
        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_sortie_delete',)]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_main');
    }

    #[Route('/cancel/{id}', name: 'app_sortie_cancel')]
    public function cancel(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        // si la sortie a déjà été annulée l'accès à cette page n'est plus possible
        if ($sortie->getEtat() && $sortie->getEtat()->getLibelle() === 'Annulée') {
            return $this->redirectToRoute('app_main');
        }

        $lieu = $sortie->getLieu();
        $ville = $lieu->getVille();

        $formCancel = $this->createForm(SortieCancel::class);
        $formCancel->handleRequest($request);

        if ($formCancel->isSubmitted()) {
            if ($formCancel->isValid()) {
                if ($formCancel->get('Enregistrer')->isClicked()) {
                    $motifAnnulation = $formCancel->get('cancelSortie')->getData();

                    $nouveauxInfosSortie = $sortie->getInfosSortie() ? $sortie->getInfosSortie() . "\n" : "";
                    $sortie->setInfosSortie($nouveauxInfosSortie . "Motif d'annulation : " . $motifAnnulation);

                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);
                    $sortie->setEtat($etat);
                }

                $entityManager->persist($sortie);
                $entityManager->flush();

                return $this->redirectToRoute('app_main', ['id' => $sortie->getId()]);
            }
        }
        return $this->render('sortie/cancel.html.twig', [
            'sortie' => $sortie,
            'ville' => $ville,
            'formCancel' => $formCancel,
        ]);
    }

}