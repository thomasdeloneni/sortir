<?php

namespace App\Form\model;

use App\Entity\Campus;
use Symfony\Component\Validator\Constraints as Assert;

class SortieSearch
{

    /**
     * @Assert\Length(max=255)
     */
    private ?string $nom = null;
    /**
     * @Assert\DateTime
     */
    private ?\DateTimeInterface $startDate = null;

    /**
     * @Assert\DateTime
     */
    private ?\DateTimeInterface $endDate = null;

    private ?Campus $campus = null;
    private ?bool $isOrganizer = null;
    private ?bool $isInscrit = null;
    private ?bool $isNotInscrit = null;
    private ?bool $isFinished = null;

    // Getters and setters for each property
    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): self
    {
        $this->campus = $campus;
        return $this;
    }

    public function getIsOrganizer(): ?bool
    {
        return $this->isOrganizer;
    }

    public function setIsOrganizer(?bool $isOrganizer): self
    {
        $this->isOrganizer = $isOrganizer;
        return $this;
    }

    public function getIsInscrit(): ?bool
    {
        return $this->isInscrit;
    }

    public function setIsInscrit(?bool $isInscrit): self
    {
        $this->isInscrit = $isInscrit;
        return $this;
    }

    public function getIsNotInscrit(): ?bool
    {
        return $this->isNotInscrit;
    }

    public function setIsNotInscrit(?bool $isNotInscrit): self
    {
        $this->isNotInscrit = $isNotInscrit;
        return $this;
    }

    public function getIsFinished(): ?bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(?bool $isFinished): self
    {
        $this->isFinished = $isFinished;
        return $this;
    }
}