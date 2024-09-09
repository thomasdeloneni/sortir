<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie')]
final class SortieController extends AbstractController
{
    #[Route(name: 'app_sortie_index')]
    public function index(SortieRepository $sortieRepository): Response
    {
        return $this->render('sortie/createUser.html.twig', [
            'sorties' => $sortieRepository->findAll(),
        ]);
    }

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
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'En cours']);
                    $sortie->setEtat($etat);
                } elseif ($form->get('Publier')->isClicked()) {
                    $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                    $sortie->setEtat($etat);
                }
                $sortie->setOrganisateur($userEnCours);
                $entityManager->persist($sortie);
                $entityManager->flush();
                return $this->redirectToRoute('app_sortie_index');
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

    #[Route('/{id}/inscrire', name: 'app_sortie_inscrire')]
    public function inscrire(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager): Response
    {
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        if (count($sortie->getParticipant()) >= $sortie->getNbInscriptionsMax()) {
            $this->addFlash('danger', 'Le nombre maximum de participants a été atteint.');
            return $this->redirectToRoute('app_main');
        }

        $user = $this->getUser();

        if (!$user instanceof Participant) {
            $this->addFlash('danger', 'Utilisateur non valide.');
            return $this->redirectToRoute('app_main');
        }

        if (!$sortie->getParticipant()->contains($user)) {
            $sortie->addParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes bien inscrit à la sortie.');
        } else {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cette sortie.');
        }

        return $this->redirectToRoute('app_main');
    }

    #[Route('/{id}/desinscrire', name: 'app_sortie_desinscrire')]
    public function desinscrire(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager, Security $security): Response
    {

        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        $user = $this->getUser();

        if (!$user instanceof Participant) {
            $this->addFlash('danger', 'Utilisateur non valide.');
            return $this->redirectToRoute('app_main');
        }

        if ($sortie->getParticipant()->contains($user)) {
            $sortie->removeParticipant($user);
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Vous vous êtes désinscrit de la sortie.');
        } else {
            $this->addFlash('danger', 'Vous n\'étiez pas inscrit à cette sortie.');
        }

        return $this->redirectToRoute('app_main');
    }

}