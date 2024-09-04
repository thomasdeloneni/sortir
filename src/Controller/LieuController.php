<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LieuController extends AbstractController
{
    // récupérer les lieux en fonction de la ville choisie par l'utilisateur
    #[Route('/api/ville/{id}', name: 'api_ville_lieu')]
    public function getLieuxByVille(int $id, EntityManagerInterface $em): JsonResponse
    {
        $ville = $em->getRepository(Ville::class)->find($id);
        if (!$ville) {
            return new JsonResponse(['message' => 'Ville non trouvée'], 404);
        }
    
        $lieux = $ville->getLieux();
        $lieuxArray = [];

        foreach ($lieux as $lieu) {
            $lieuxArray[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
            ];
        }
        return new JsonResponse($lieuxArray);
    }

    // récupérer les informations d'un lieu 
    #[Route('/api/lieu/{id}', name: 'api_lieu_informations')]
    public function informations(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lieu = $em->getRepository(Lieu::class)->find($id);
    
        if (!$lieu) {
            return new JsonResponse(['message' => 'Lieu non trouvé'], 404);
        }
    
        // Retourner les données du lieu en JSON
        return $this->json([
            'id' => $lieu->getId(),
            'rue' => $lieu->getRue(),
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude(),
        ], 200);
    }
}
