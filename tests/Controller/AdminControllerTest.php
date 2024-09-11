<?php

namespace App\Tests\Controller;

use App\Entity\Campus;
use App\Entity\Participant;
use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminControllerTest extends WebTestCase
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

    private function createAdminUser(): Participant
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

    // Test pour la route /admin
    public function testAdminAccess(): void
    {
        // Simuler la connexion avec l'utilisateur admin
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        // Accéder à la page d'administration après la connexion
        $this->client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // Test pour la liste des utilisateurs - admin
    public function testUserView(): void
    {
        // Connexion de l'admin
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        // Acces à la page de la liste
        $this->client->request('GET', '/admin/user/view');

        // Vérifier que la réponse est un succès (code 200)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que le contenu de la page contient les éléments attendus
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertSelectorExists('.userView');
    }

    // Test création des utilisateurs - admin
    public function testNewUser(): void
    {
        // Connexion de l'admin
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        // Acces à la page du formulaire de création
        $formCreate = $this->client->request('GET', '/admin/user/new');

        // Vérifier que la réponse est un succès (code 200)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que le contenu de la page contient les éléments attendus
        $this->assertSelectorTextContains('h1', 'Créer un nouvel utilisateur');
        $this->assertSelectorExists('.user_new');


        // Soumettre le formulaire
        $form = $formCreate->selectButton('Créer l\'utilisateur')->form([
            'profil[pseudo]' => 'testeurUser',
            'profil[prenom]' => 'simple testeur',
            'profil[nom]' => 'steeve',
            'profil[telephone]' => '0269753648',
            'profil[roles]' => ['ROLE_USER'],
            'profil[mail]' => 'testeurUser@example.com',
            'profil[password][first]' => 'password9',
            'profil[password][second]' => 'password9',
        ]);
        $this->client->submit($form);

        // Vérifier que le nouvel utilisateur a été ajouté dans la base de données
        $user = $this->entityManager->getRepository(Participant::class)->findOneBy(['pseudo' => 'testeurUser']);
        $this->assertNotNull($user);
        $this->assertEquals('testeurUser', $user->getPseudo());
    }

    //Création d'un utilisateur a supprimer
    private function createUserToDelete(): Participant
    {
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $campus = new Campus();
        $campus->setNom('Lille');
        $this->entityManager->persist($campus);
        $this->entityManager->flush();

        $user = new Participant();
        $user->setPseudo('user_to_delete');
        $user->setRoles(['ROLE_USER']);
        $user->setNom('User');
        $user->setPrenom('Test');
        $user->setTelephone('0123456789');
        $user->setMail('user_to_delete@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setCampus($campus);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return $user;
    }


    // Test suppresion des utilisateurs - admin
    public function testViewUserDelete(): void
    {
        // Connexion de l'admin
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);

        // Création d'un utilisateur à supprimer
        $userToDelete = $this->createUserToDelete();

        // Acces à la page de la liste
        $this->client->request('GET', '/admin/user/view');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertSelectorExists('.userView');

        // Suppression de l'utilisateur
        $this->client->request('GET', '/admin/user/view/delete/' . $userToDelete->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/admin/user/view');

        // Vérifier que le nouvel utilisateur a été retiré la base de données
        $deletedUser = $this->entityManager->getRepository(Participant::class)->find($userToDelete->getId());
        $this->assertNull($deletedUser);
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
