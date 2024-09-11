<?php

namespace App\DataFixtures;

use App\Service\FileUploader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Etat;
use Faker\Generator;
use App\Entity\Ville;
use Faker\Factory;
use App\Entity\Lieu;
use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Sortie;

class AppFixtures extends Fixture
{
    private readonly Generator $faker;

    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->faker = Factory::create('fr_FR');

    }

    public function load(ObjectManager $manager): void
    {
        $this->addEtats($manager);
        $this->addVilles(10, $manager);
        $this->addLieux(20, $manager);
        $this->addCampus($manager);
        $this->addParticipants(20, $manager);
        $this->addSorties(20, $manager);
        $this->addSpecificUser($manager);
    }

    public function addEtats(ObjectManager $manager): void
    {
        $libelles = ['Créée', 'Ouverte', 'Clôturée','En cours', 'Activité en cours', 'Passée', 'Annulée', 'Historisée'];

        foreach ($libelles as $libelle) {
            $etat = new Etat();
            $etat->setLibelle($libelle);
            $manager->persist($etat);
        }

        $manager->flush();
    }

    public function addVilles(int $number, ObjectManager $manager): void
    {
        for ($i = 0; $i < $number; $i++) {
            $ville = new Ville();
            $ville->setNom($this->faker->city());
            $ville->setCodePostal($this->faker->postcode());
            $manager->persist($ville);
        }

        $manager->flush();
    }

    public function addLieux(int $number, ObjectManager $manager): void
    {

        $villes = $manager->getRepository(Ville::class)->findAll();
        for ($i = 0; $i < $number; $i++) {
            $lieu = new Lieu();
            $lieu->setNom($this->faker->company());
            $lieu->setRue($this->faker->streetAddress());
            $lieu->setLatitude($this->faker->latitude());
            $lieu->setLongitude($this->faker->longitude());
            $lieu->setVille($this->faker->randomElement($villes));
            $manager->persist($lieu);
        }

        $manager->flush();
    }

    public function addCampus(ObjectManager $manager): void
    {
        $campus = ['Saint-Herblain', 'Niort', 'La Roche-sur-Yon', 'Chartres-de-Bretagne'];

        foreach ($campus as $campu) {
            $campus = new Campus();
            $campus->setNom($campu);
            $manager->persist($campus);
        }

        $manager->flush();
    }

    public function addParticipants(int $number, ObjectManager $manager)
    {

        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $campus = $manager->getRepository(Campus::class)->findAll();

        for ($i = 0; $i < $number; $i++) {
            $participant = new Participant();
            $participant->setPseudo($this->faker->userName());
            $participant->setRoles([$this->faker->randomElement($roles)]);
            $participant->setNom($this->faker->lastName());
            $participant->setPrenom($this->faker->firstName());
            $participant->setTelephone($this->faker->phoneNumber());
            // $participant->setImageFilename('public/img/userWho.png');
            $participant->setMail($this->faker->email());
            $participant->setCampus($this->faker->randomElement($campus));
            $participant->setPassword($this->userPasswordHasher->hashPassword($participant, 'password'));
            $manager->persist($participant);
        }

        $manager->flush();
    }

    public function addSorties(int $number, ObjectManager $manager)
    {
        $etats = $manager->getRepository(Etat::class)->findAll();
        $lieux = $manager->getRepository(Lieu::class)->findAll();
        $participants = $manager->getRepository(Participant::class)->findAll();
        $campus = $manager->getRepository(Campus::class)->findAll();
        for ($i = 0; $i < $number; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($this->faker->sentence(3));
            $dateHeureDebut = $this->faker->dateTimeBetween('now', '+1 year');
            $dateLimiteInscription = $this->faker->dateTimeBetween('now', $dateHeureDebut);
            $sortie->setDateHeureDebut($dateHeureDebut);
            $sortie->setDuree($this->faker->numberBetween(1, 10) * 60);
            $sortie->setDateLimiteInscription($dateLimiteInscription);
            $nbInscriptionsMax = $this->faker->numberBetween(5, 20);
            $sortie->setNbInscriptionsMax($nbInscriptionsMax);
            $sortie->setInfosSortie($this->faker->text());
            $sortie->setEtat($this->faker->randomElement($etats));
            $sortie->setLieu($this->faker->randomElement($lieux));
            $sortie->setOrganisateur($this->faker->randomElement($participants));
            $sortie->setCampus($this->faker->randomElement($campus));
            $nbParticipants = $this->faker->numberBetween(1, $nbInscriptionsMax);

            for ($i = 0; $i < $nbParticipants; $i++) {
                $participant = $this->faker->randomElement($participants);
                if (!$sortie->getParticipant()->contains($participant)) {
                    $sortie->addParticipant($participant);
                }
            }

            $manager->persist($sortie);
        }
        $manager->flush();
    }

    public function addSpecificUser(ObjectManager $manager): void
    {
        $existingUser = $manager->getRepository(Participant::class)->findOneBy(['pseudo' => 'user']);
        if (!$existingUser) {
            $participant = new Participant();
            $participant->setPseudo('admin');
            $participant->setRoles(['ROLE_ADMIN']);
            $participant->setNom('User');
            $participant->setPrenom('User');
            $participant->setTelephone('0000000000');
            // $participant->setImageFilename('public/img/userWho.png');
            $participant->setMail('user@example.com');
            $participant->setCampus($manager->getRepository(Campus::class)->findOneBy([]));
            $participant->setPassword($this->userPasswordHasher->hashPassword($participant, 'password'));
            $manager->persist($participant);
            $manager->flush();
        }
    }

}