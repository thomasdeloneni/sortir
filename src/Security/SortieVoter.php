<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Sortie;
use App\Entity\Participant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SortieVoter extends Voter
{
    const INSCRIRE = 'inscrire';
    const DESINSCRIRE = 'desinscrire';

    protected function supports(string $attribute, $subject): bool
    {
        // Only vote on Sortie objects inside this voter
        if ($attribute !== self::INSCRIRE) {
            return false;
        }

        if (!$subject instanceof Sortie) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Participant){
            return false;
        }

        /**
         * @Var Sortie $sortie
         */
        $sortie = $subject;

        return match ($attribute) {
            self::INSCRIRE => $this->canInscrire($sortie, $user),
            self::DESINSCRIRE => $this->canDesinscrire($sortie, $user),
            default => false,
        };
    }

    private function canInscrire(Sortie $sortie, Participant $user): bool
    {

        if ($sortie->getParticipant()->contains($user)
            && count($sortie->getParticipant()) >= $sortie->getNbInscriptionsMax()
            && $sortie->getEtat()->getLibelle() === 'Ouverte') {
            return false;
        }
        return true;
    }

    private function canDesinscrire(Sortie $sortie, Participant $user): bool
    {
        if ($sortie->getParticipant()->contains($user)
            && ($sortie->getEtat()->getLibelle() === 'Ouverte' || $sortie->getEtat()->getLibelle() === 'Clôturée')) {
            return true;
        }
        return false;
    }

}