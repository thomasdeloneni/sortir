<?php

namespace App\Controller;

use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function profileUpdate(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imageDirectory): Response
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
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($imageDirectory, $newFilename);
                } catch (FileException $e){

                }
                $participant->setImageFilename($newFilename);
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

}

