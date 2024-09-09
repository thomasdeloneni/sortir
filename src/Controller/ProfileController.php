<?php

namespace App\Controller;

use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ProfileController extends AbstractController
{

    #[Route('/profile', name: 'app_profile')]
    public function profile(UserInterface $participant): Response
    {
        return $this->render('profile/profile.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/profile/update', name: 'app_profile_update')]
    public function profileUpdate(
        Request $request, EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        FileUploader $fileUploader
    ): Response
    {
        $participant = $this->getUser();
        $profilForm = $this->createForm(ProfilType::class, $participant);
        $profilForm->handleRequest($request);

        if($profilForm->isSubmitted() && $profilForm->isValid()){
            $modifPassword = $profilForm->get('password')->getData();

            if($modifPassword){
                $hashedPassword = $passwordHasher->hashPassword($participant, $modifPassword);
                $participant->setPassword($hashedPassword);
            }

            // Gestion du fichier image
            $imageFile = $profilForm->get('image')->getData();
            if($imageFile){

                $oldImageFilename = $participant->getImageFilename();
                if ($oldImageFilename && $oldImageFilename !== 'imPro.png') {
                    $fileUploader->remove($oldImageFilename);
                }

                $originalFileName = $fileUploader->upload($imageFile);
                $participant->setImageFilename($originalFileName);
            }

            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'Le profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/profile_update.html.twig', [
            'participant' => $participant,
            'profilForm' => $profilForm->createView(),
        ]);
    }

    #[Route('/profile/{id}', name: 'app_profile_id')]
    public function profileUserId(int $id, ParticipantRepository $participantRepository, Security $security): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $participantRepository->find($id);
        $currentUser = $security->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Participant not found');
        }

        return $this->render('profile/profile.html.twig', [
            'participant' => $user,
            'current_user' => $currentUser,
        ]);
    }


}

