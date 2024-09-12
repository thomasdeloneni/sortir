<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Repository\EtatRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

final class SortieControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    private Participant $participant;

    private Sortie $sortie;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->purgeDatabase();
        $this->loadFixtures();

        $this->participant = $this->createParticipant();
        $this->sortie = $this->createSortie();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();
    }

    private function loadFixtures(): void
    {
        $loader = new Loader();
        $loader->addFixture(new AppFixtures($this->passwordHasher));

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute($loader->getFixtures());
    }

    // création d'un utilisateur test
    private function createParticipant(): Participant
    {
        //création d'un campus
        $campus = new Campus();
        $campus->setNom('Rennes');

        //je persiste le campus en BDD
        $this->entityManager->persist($campus);
        $this->entityManager->flush();

        // Création d'un nouveau participant
        $user = new Participant();
        $user->setNom('Starfish');
        $user->setPrenom('Patrick');
        $user->setCampus($campus);
        $user->setMail('patstarfish@mer.com');
        $user->setPseudo('Bob');
        $user->setTelephone('0606060606');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);

        // Je persiste le participant en BDD
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    ////////////////////////////////////////////////////////////////////////////////

    // création d'une sortie test
    public function createSortie(): Sortie {

        $etat = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);

        $campus = new Campus();
        $campus->setNom('Rennes');
        $this->entityManager->persist($campus);
        $this->entityManager->flush();

        $ville = new Ville();
        $ville->setNom("Angers");
        $ville->setCodePostal("49000");
        $this->entityManager->persist($ville);
        $this->entityManager->flush();

        $lieu = new Lieu();
        $lieu->setNom("Get out");
        $lieu->setRue("2 rue du mabilais");
        $lieu->setVille($ville);

        $this->entityManager->persist($lieu);
        $this->entityManager->flush();

        $sortie = new Sortie();
        $sortie->setNom('Escape Game');
        $sortie->setDateLimiteInscription(new \DateTime('2025-01-17 00:03:39'));
        $sortie->setDateHeureDebut(new \DateTime('2025-02-19 00:03:39'));
        $sortie->setNbInscriptionsMax(20);
        $sortie->setInfosSortie('Réussir à sortir');
        $sortie->setDuree(20);
        $sortie->setEtat($etat);
        $sortie->setLieu($lieu);
        $sortie->setCampus($campus);
        $sortie->setOrganisateur($this->participant);

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        if ($sortie->getId() === null) {
            throw new \Exception('La sortie n\'a pas été correctement persistée.');
        }

        return $sortie;
    }

    //test --> si l'user est connecté il a accès à la page de création de sortie
    public function testIfParticipantConnectedForPageSortie(): void
    {
        // Création du client
        $this->client->loginUser($this->participant);

        // Accéder à la page
        $this->client->request('GET', '/sortie/');

        // Vérifier que l'utilisateur reste sur la page (statut 200)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    ////////////////////////////////////////////////////////////////////////////////

    //test --> si le participant n'est pas connecté il n'a pas accès à la page de création de sortie
    public function testIfParticipantIsNotConnectedForPageSortie(): void
    {
        // Accéder à la page
        $this->client->request('GET', '/sortie/');

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->assertResponseRedirects('/login');
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> vérifier si le formulaire de création de sortie est enregistré
    public function testIfNewSortieFormIsSaved(): void {
        // je simule la connexion de l'utilisateur
        $this->client->loginUser($this->participant);

        // j'accède à la page du formulaire
        $crawler = $this->client->request('GET', '/sortie/');

        // Soumettre le formulaire avec le bouton "Enregistrer"
        $form = $crawler->selectButton('Enregistrer')->form([
            'sortie[nom]' => 'Disneyland',
            'sortie[dateHeureDebut]' => '2025-02-19 00:03:39',
            'sortie[duree]' => '20',
            'sortie[dateLimiteInscription]' => '2025-02-17 00:03:39',
            'sortie[nbInscriptionsMax]' => '20',
            'sortie[infosSortie]' => 'Faire des attractions',
        ]);
        $this->client->submit($form);

        // Vérifier que la redirection a bien eu lieu
        $this->assertResponseRedirects('/');

        // Suivre la redirection
        $this->client->followRedirect();

        // Vérifier que l'état de la sortie est 'Créée'
        $etatRepository = static::getContainer()->get(EtatRepository::class);
        $sortie = $etatRepository->findOneBy(['libelle' => 'Créée']);
        $this->assertNotNull($sortie);
        $this->assertEquals('Créée', $sortie->getLibelle());
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> vérifier si le formulaire de création de sortie est annulé
    public function testIfNewSortieFormIsCancelled(): void
    {
        $this->client->loginUser($this->participant);

        $this->client->request('GET', '/sortie/');

        $crawler = $this->client->clickLink('Annuler');

        // vérifier la page avec le nom de l'utilisateur
        $this->assertStringContainsString(
            $this->participant->getNom(),
            $crawler->filter('body')->text()
        );

        // ce n'est pas une redirection, mais un lien cliquable = 200
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> l'affichage d'une sortie
    public function testShowSortie(): void {

        //on log l'utilisateur
        $this->client->loginUser($this->participant);

        $crawler = $this->client->request('GET', '/sortie/' . $this->sortie->getId());
        var_dump($crawler);

        $this->assertStringContainsString(
            $this->sortie->getDuree(),
            $crawler->filter('body')->text()
        );
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> Modifier une sortie si elle est publiée
    public function testEditSortieIfPublished(): void {
        $this->client->loginUser($this->participant);

        $crawler = $this->client->request('GET', sprintf('/sortie/%d/edit', $this->sortie->getId()));

        $form = $crawler->selectButton('Publier')->form([
                'sortie[nom]' => 'Crêperie',
        ]);

        $etat = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
        $this->sortie->setEtat($etat);

        $this->client->submit($form);

        $sortieModifiee = $this->entityManager->getRepository(Sortie::class)->find($this->sortie->getId());

        // vérifier l'état et le nouveau nom pour la sortie
        $this->assertEquals('Crêperie', $sortieModifiee->getNom());

        $this->assertEquals('Ouverte', $sortieModifiee->getEtat()->getLibelle());
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> Modifier une sortie si elle est enregistrée
    public function testEditSortieIfSaved(): void {
        $this->client->loginUser($this->participant);

        $crawler = $this->client->request('GET', sprintf('/sortie/%d/edit', $this->sortie->getId()));

        $form = $crawler->selectButton('Enregistrer')->form([
            'sortie[nom]' => 'Bar',
        ]);

        $this->client->submit($form);

        $sortieModifiee = $this->entityManager->getRepository(Sortie::class)->find($this->sortie->getId());

        // vérifier le nouveau nom de la sortie
        $this->assertEquals('Bar', $sortieModifiee->getNom());
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> vérifier si le formulaire de modification de sortie est annulé
    public function testIfEditSortieFormIsCancelled(): void
    {
        $this->client->loginUser($this->participant);

        $this->client->request('GET', sprintf('/sortie/%d/edit', $this->sortie->getId()));

        $crawler = $this->client->clickLink('Annuler');

        // vérifier la page avec le nom de l'utilisateur
        $this->assertStringContainsString(
            $this->participant->getNom(),
            $crawler->filter('body')->text()
        );

        // ce n'est pas une redirection, mais un lien cliquable = 200
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    ////////////////////////////////////////////////////////////////////////////////

    // test --> vérifier si la sortie est bien annulée
    public function testIfSortieIsCancelled(): void
    {
        $this->client->loginUser($this->participant);

        $crawler = $this->client->request('GET', '/sortie/cancel/' . $this->sortie->getId());

        $form = $crawler->selectButton('Enregistrer')->form();

        $etat = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);

        $this->sortie->setEtat($etat);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $sortieAnnulee = $this->entityManager->getRepository(Sortie::class)->find($this->sortie->getId());

        // vérifier l'état de la sortie
        $this->assertEquals('Annulée', $sortieAnnulee->getEtat()->getLibelle());
    }
}
