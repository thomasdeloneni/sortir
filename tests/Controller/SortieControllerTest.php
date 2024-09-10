<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
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

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->purgeDatabase();
        $this->loadFixtures();

        $this->participant = $this->createParticipant();
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

//
//    public function testShow(): void
//    {
//        $this->markTestIncomplete();
//        $fixture = new Sortie();
//        $fixture->setNom('My Title');
//        $fixture->setDateHeureDebut('My Title');
//        $fixture->setDuree('My Title');
//        $fixture->setDateLimiteInscription('My Title');
//        $fixture->setNbInscriptionsMax('My Title');
//        $fixture->setInfosSortie('My Title');
//        $fixture->setEtat('My Title');
//        $fixture->setLieu('My Title');
//        $fixture->setCampus('My Title');
//        $fixture->setParticipant('My Title');
//        $fixture->setOrganisateur('My Title');
//
//        $this->manager->persist($fixture);
//        $this->manager->flush();
//
//        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
//
//        self::assertResponseStatusCodeSame(200);
//        self::assertPageTitleContains('Sortie');
//
//        // Use assertions to check that the properties are properly displayed.
//    }
//
//    public function testEdit(): void
//    {
//        $this->markTestIncomplete();
//        $fixture = new Sortie();
//        $fixture->setNom('Value');
//        $fixture->setDateHeureDebut('Value');
//        $fixture->setDuree('Value');
//        $fixture->setDateLimiteInscription('Value');
//        $fixture->setNbInscriptionsMax('Value');
//        $fixture->setInfosSortie('Value');
//        $fixture->setEtat('Value');
//        $fixture->setLieu('Value');
//        $fixture->setCampus('Value');
//        $fixture->setParticipant('Value');
//        $fixture->setOrganisateur('Value');
//
//        $this->manager->persist($fixture);
//        $this->manager->flush();
//
//        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));
//
//        $this->client->submitForm('Update', [
//            'sortie[nom]' => 'Something New',
//            'sortie[dateHeureDebut]' => 'Something New',
//            'sortie[duree]' => 'Something New',
//            'sortie[dateLimiteInscription]' => 'Something New',
//            'sortie[nbInscriptionsMax]' => 'Something New',
//            'sortie[infosSortie]' => 'Something New',
//            'sortie[etat]' => 'Something New',
//            'sortie[lieu]' => 'Something New',
//            'sortie[campus]' => 'Something New',
//            'sortie[participant]' => 'Something New',
//            'sortie[organisateur]' => 'Something New',
//        ]);
//
//        self::assertResponseRedirects('/sortie/');
//
//        $fixture = $this->repository->findAll();
//
//        self::assertSame('Something New', $fixture[0]->getNom());
//        self::assertSame('Something New', $fixture[0]->getDateHeureDebut());
//        self::assertSame('Something New', $fixture[0]->getDuree());
//        self::assertSame('Something New', $fixture[0]->getDateLimiteInscription());
//        self::assertSame('Something New', $fixture[0]->getNbInscriptionsMax());
//        self::assertSame('Something New', $fixture[0]->getInfosSortie());
//        self::assertSame('Something New', $fixture[0]->getEtat());
//        self::assertSame('Something New', $fixture[0]->getLieu());
//        self::assertSame('Something New', $fixture[0]->getCampus());
//        self::assertSame('Something New', $fixture[0]->getParticipant());
//        self::assertSame('Something New', $fixture[0]->getOrganisateur());
//    }
//
//    public function testRemove(): void
//    {
//        $this->markTestIncomplete();
//        $fixture = new Sortie();
//        $fixture->setNom('Value');
//        $fixture->setDateHeureDebut('Value');
//        $fixture->setDuree('Value');
//        $fixture->setDateLimiteInscription('Value');
//        $fixture->setNbInscriptionsMax('Value');
//        $fixture->setInfosSortie('Value');
//        $fixture->setEtat('Value');
//        $fixture->setLieu('Value');
//        $fixture->setCampus('Value');
//        $fixture->setParticipant('Value');
//        $fixture->setOrganisateur('Value');
//
//        $this->manager->persist($fixture);
//        $this->manager->flush();
//
//        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
//        $this->client->submitForm('Delete');
//
//        self::assertResponseRedirects('/sortie/');
//        self::assertSame(0, $this->repository->count([]));
//    }
}
