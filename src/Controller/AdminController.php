<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/user/view', name: 'admin_user_view')]
    public function viewUser(ParticipantRepository $participantRepository, Security $security): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user_view = $participantRepository->findAll();
        $currentUser = $security->getUser();

        return $this->render('admin/user_view.html.twig', [
            'userView' => $user_view,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/admin/user/new', name: 'admin_user_new')]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new Participant();
        $isAdmin = true;
        $form = $this->createForm(ProfilType::class, $user, ['is_admin' => $isAdmin]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFileName = $fileUploader->upload($imageFile);
                $user->setImageFilename($originalFileName);
            } else {
                $user->setImageFilename('imPro.png');
            }
            // Gestion du rôle (via le formulaire)
            $roles = $form->get('roles')->getData();
            $user->setRoles($roles);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/user_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

//    #[Route('/admin/user/view/delete/{id}', name: 'admin_user_view_delete')]
//    public function viewUserDelete(
//        int $id,
//        FileUploader $fileUploader,
//        ParticipantRepository $participantRepository,
//        SortieRepository $sortieRepository,
//        EntityManagerInterface $em,
//        RequestStack $requestStack,
//        Security $security
//    ): Response {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');
//
//        // Récupérez l'utilisateur connecté
//        $currentUser = $this->getUser();
//        if (!$currentUser || !$currentUser->getId()) {
//            throw $this->createAccessDeniedException('Vous devez être connecté pour effectuer cette action.');
//        }
//
//        $user = $participantRepository->find($id);
//
//        // Vérifiez si l'utilisateur existe
//        if (!$user) {
//            throw $this->createNotFoundException('Participant non trouvé.');
//        }
//
//        // Suppression des fichiers associés
//        $oldPictures = $user->getImageFilename();
//        if ($oldPictures) {
//            $fileUploader->remove($oldPictures);
//        }
//
//        // Récupérer les sorties organisées par cet utilisateur et les dissocier
//        $organisateurSorties = $sortieRepository->findBy(['organisateur' => $user->getId()]);
//        foreach ($organisateurSorties as $sortie) {
//            $sortie->setOrganisateur(NULL);
//            $em->persist($sortie);
//        }
//
//        // Suppression de l'utilisateur
//        $em->remove($user);
//        $em->flush();
//
//        // Si l'utilisateur supprimé est celui connecté, on force une déconnexion
//        if ($currentUser && $currentUser->getId() === $id) {
//            // Invalider la session
//            $requestStack->getSession()->invalidate();
//            // Déconnecter l'utilisateur
//            return $this->redirectToRoute('app_logout');
//        }
//
//        return $this->redirectToRoute('admin_user_view');
//    }

}
