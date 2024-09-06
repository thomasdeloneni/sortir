<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateStatusService
{
    private SortieRepository $sortieRepository;
    private EntityManagerInterface $entityManager;
    private EtatRepository $etatRepository;

    public function __construct(
        SortieRepository $sortieRepository,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository
    ) {
        $this->sortieRepository = $sortieRepository;
        $this->entityManager = $entityManager;
        $this->etatRepository = $etatRepository;
    }

    public function updateStatus(): void
    {
        $sorties = $this->sortieRepository->findAll();
        dump($sorties);
        foreach ($sorties as $sortie) {
            $currentDate = new \DateTime();
            $dateLimiteInscription = $sortie->getDateLimiteInscription();
            $dateDebut = $sortie->getDateHeureDebut();
            $dateIn1Month = clone $dateDebut;
            $dateIn1Month->modify('+1 month');

            if ($currentDate > $dateIn1Month) {
                $this->setStateByLibelle($sortie, 'Historisée');
                continue;
            }
            if ($currentDate >= $dateDebut) {
                $this->setStateByLibelle($sortie, 'En cours');
                continue;
            }
            if ($currentDate > $dateLimiteInscription) {
                $this->setStateByLibelle($sortie, 'Cloturée');
            }
        }
    }

    public function setStateByLibelle(Sortie $sortie, string $libelle): void
    {
        $etat = $this->etatRepository->findOneBy(['libelle' => $libelle]);
        $sortie->setEtat($etat);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }
}


