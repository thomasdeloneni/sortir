<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieCancel;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
final class SortieController extends AbstractController
{

    #[Route('/', name: 'app_sortie_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortie = new Sortie();
        $userEnCours = $this->getUser();
        $sortie->setCampus($userEnCours->getCampus());

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $userEnCours = $this->getUser();

        if (!$userEnCours instanceof Participant) {
            throw new \LogicException('User n\'est pas un participant');
        }

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
    public function sortieDetail(int $id, EntityManagerInterface $entityManager): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);

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
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);

        if (!$this->isGranted('edit', $sortie)) {
         return $this->redirectToRoute('app_main');
        }

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
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);

        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_main');
    }

    #[Route('/cancel/{id}', name: 'app_sortie_cancel')]
    public function cancel(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
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

                return $this->redirectToRoute('app_main');
            }
        }
        return $this->render('sortie/cancel.html.twig', [
            'sortie' => $sortie,
            'ville' => $ville,
            'formCancel' => $formCancel,
        ]);
    }


    #[Route('/{id}/inscrire', name: 'app_sortie_inscrire')]
    public function inscrire(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour vous inscrire à une sortie.');
            return $this->redirectToRoute('app_login');
        }

        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        $this->denyAccessUnlessGranted('inscrire', $sortie);


        if (!$user instanceof \App\Entity\Participant) {
            throw new \LogicException('User n\'est pas un participant');
        }

        $sortie->addParticipant($user);
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'Vous êtes bien inscrit à la sortie.');

        return $this->redirectToRoute('app_main');
    }

    #[Route('/{id}/desinscrire', name: 'app_sortie_desinscrire')]
    public function desinscrire(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour vous désinscrire à une sortie.');
            return $this->redirectToRoute('app_login');
        }

        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        $this->denyAccessUnlessGranted('desinscrire', $sortie);

        if (!$user instanceof \App\Entity\Participant) {
            throw new \LogicException('User n\'est pas un participant');
        }


        $sortie->removeParticipant($user);
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'Vous vous êtes désinscrit de la sortie.');


        return $this->redirectToRoute('app_main');
    }

}