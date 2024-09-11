<?php

namespace App\Tests\Controller;


use App\DataFixtures\AppFixtures;
use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends WebTestCase
{
    private $client; // Client HTTP pour simuler des requêtes dans les tests
    private $entityManager;

    protected function setUp(): void
    {
        // Initialiser le client pour simuler des requêtes HTTP
        $this->client = static::createClient();

        // Récupérer l'EntityManager
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $this->purgeDatabase();
        $this->loadFixtures();
    }

    private function purgeDatabase(): void
    {
        // Créer un purger pour vider la base de données
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();
    }
    private function loadFixtures(): void
    {
        // Purger la base de données avant de recharger les fixtures
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Charger les fixtures avec DoctrineFixturesBundle
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(self::getContainer()->get(UserPasswordHasherInterface::class)));

        // Créer un exécuteur pour les fixtures
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures(), true);
    }

    // Creation d'un utilisateur 'user', pour les tests
    private function testCreateUser(): Participant
    {
        // Récupérer le service de hachage de mot de passe
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $campus = new Campus();
        $campus->setNom('Niort');
        $this->entityManager->persist($campus);
        $this->entityManager->flush();

        $user = new Participant();
        $user->setPseudo('testeur');
        $user->setNom('lemarchand');
        $user->setPrenom('antoine');
        $user->setMail('testeur@gmail.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, 'testeur'));
        $user->setTelephone('0758962359');
        $user->setCampus($campus);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    // Creation d'un utilisateur 'admin', pour les tests
    private function createAdminUserTest(): Participant
    {
        // Récupérer le service de hachage de mot de passe
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        //création d'un campus
        $campus = new Campus();
        $campus->setNom('Rennes');

        //je persiste le campus en BDD
        $this->entityManager->persist($campus);
        $this->entityManager->flush();

        // Créer un nouvel utilisateur administrateur
        $admin = new Participant();
        $admin->setPseudo('admin_test');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNom('Admin');
        $admin->setPrenom('Test');
        $admin->setTelephone('0600000000');
        $admin->setMail('admin_test@example.com');
        $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
        $admin->setCampus($campus);

        // Persister l'utilisateur dans la base de données
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }

    // Testez l'accès à la page de profil
    public function testProfilePageAccess()
    {

        // Simuler la connexion de l'utilisateur
        $user = $this->testCreateUser();
        $this->client->loginUser($user);

        $userTitle = $user->getPseudo();

        // Accéder à la page
        $this->client->request('GET', '/profile');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', $userTitle);
        $this->assertSelectorExists('.profil');
    }

    // Testez la mise à jour de la page de profil
    public function testProfileUpdate()
    {

        // Simuler la connexion de l'utilisateur
        $user = $this->testCreateUser();
        $this->client->loginUser($user);

        //Verifier l'état actuel du pseudo de l'utilisateur creer dans la fonction testCreateUser()
        $user = $this->entityManager->getRepository(Participant::class)->findOneBy(['pseudo' => 'testeur']);
        $this->assertNotNull($user);
        $this->assertEquals('testeur', $user->getPseudo());

        // Accéder à la page
        $updateForm = $this->client->request('GET', '/profile/update');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Mon Profil');
        $this->assertSelectorExists('.profilUpdate');

        // Soumettre le formulaire de mofification du profil de l'utilisateur creer dans la fonction testCreateUser()
        $form = $updateForm->selectButton('Enregistrer')->form([
            'profil[pseudo]' => 'testeurModif',
            'profil[prenom]' => 'simple testeur',
            'profil[nom]' => 'steeve',
            'profil[telephone]' => '0269753648',
            'profil[mail]' => 'testeurUser@example.com',
            'profil[password][first]' => 'password',
            'profil[password][second]' => 'password',
        ]);
        $this->client->submit($form);

        // Vérifier que les donnée de l'utilisateur ont été modifier en base et vérifier l'état du pseudo de l'utilisateur modifier
        $updateUser = $this->entityManager->getRepository(Participant::class)->findOneBy(['pseudo' => 'testeurModif']);
        $this->assertNotNull($updateUser);
        $this->assertEquals('testeurModif', $updateUser->getPseudo());

    }

    // Testez l'accès à la page de profil d'un autre utilisateur, en tant qu'admin
    public function testProfileUserId()
    {

        // Simuler la connexion avec l'utilisateur admin
        $userAdmin = $this->createAdminUserTest();
        $this->client->loginUser($userAdmin);

        // Création d'un utilisateur pour visiter son profil
        $userToVisit = $this->testCreateUser();
        $userToVisitPseudo = $userToVisit->getPseudo();

        // Acces à la page de la liste
        $this->client->request('GET', '/admin/user/view');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertSelectorExists('.userView');

        // Visite du profile via le lien de la page
        $this->client->request('GET', '/profile/' . $userToVisit->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', $userToVisitPseudo);
        $this->assertSelectorExists('.profil');
    }
    protected function tearDown(): void
    {
        // Appelé après chaque test pour effectuer le nettoyage
        parent::tearDown();

        // Fermer l'EntityManager pour éviter les fuites de mémoire
        $this->entityManager->close();
        $this->entityManager = null;
    }

}
