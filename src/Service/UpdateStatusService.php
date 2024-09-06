<?php

namespace App\Service;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UpdateStatusService
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
         private readonly EntityManagerInterface $entityManager,
        private readonly EtatRepository $etatRepository
    ) {

    }

    /**
     * @throws Exception
     */
    public function updateStatus(): void
    {
        $sorties = $this->sortieRepository->findAll();
        foreach ($sorties as $sortie) {
            $currentDate = new \DateTime();
            $dateLimiteInscription = $sortie->getDateLimiteInscription();
            $dateDebut = $sortie->getDateHeureDebut();
            $duree = $sortie->getDuree();
            $dateFin = (clone $dateDebut)->add(new \DateInterval('PT' . $duree . 'M'));
            $dateIn1Month = clone $dateDebut;
            $dateIn1Month->modify('+1 month');

            if ($currentDate > $dateIn1Month) {
                $this->setStateByLibelle($sortie, 'Historisée');
            } elseif ($currentDate >= $dateDebut && $currentDate <= $dateFin) {
                $this->setStateByLibelle($sortie, 'Activité en cours');
            } elseif ($currentDate > $dateFin) {
                $this->setStateByLibelle($sortie, 'Passée');
            } elseif ($currentDate >= $dateLimiteInscription && $currentDate < $dateDebut) {
                $this->setStateByLibelle($sortie, 'Clôturée');
            }
        }
        $this->entityManager->flush();
    }

    public function setStateByLibelle(Sortie $sortie, string $libelle): void
    {
        $etat = $this->etatRepository->findOneBy(['libelle' => $libelle]);
        $sortie->setEtat($etat);
        $this->entityManager->persist($sortie);
    }
}


