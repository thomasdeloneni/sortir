<?php

namespace App\Controller;

use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function profileUpdate(Request $request, EntityManagerInterface $em, UserInterface $participant): Response
    {
        $profilForm = $this->createForm(ProfilType::class, $participant);
        $profilForm->handleRequest($request);

        if($profilForm->isSubmitted() && $profilForm->isValid()){
            $em->flush();
            $this->addFlash('succès', 'on a valid" le formulaire !');
            return $this->redirectToRoute('app_main');
        }
        return $this->render('profile/profile_update.html.twig', [
            'participant' => $participant,
            'profilForm' => $profilForm->createView(),
        ]);
    }
}

