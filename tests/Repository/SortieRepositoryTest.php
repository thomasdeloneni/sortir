<?php

namespace App\Tests\Repository;

use App\DataFixtures\AppFixtures;
use App\Entity\Participant;
use App\Form\model\SortieSearch;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SortieRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $userPasswordHasher;
    private SessionInterface $session;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->session = self::getContainer()->get('session');
        $this->purgeDatabase();
        $this->loadFixtures();
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
        $loader->addFixture(new AppFixtures($this->userPasswordHasher));

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute($loader->getFixtures());
    }

//    public function testFindByFiltersReturnsResultsBasedOnIsInscrit(): void
//    {
//        $userIds = $this->entityManager->getRepository(Participant::class)->createQueryBuilder('p')
//            ->select('p.id')
//            ->where('p.pseudo != :admin')
//            ->setParameter('admin', 'admin')
//            ->getQuery()
//            ->getArrayResult();
//
//        $randomId = $userIds[array_rand($userIds)]['id'];
//
//        $user = $this->entityManager->getRepository(Participant::class)->find($randomId);
//
//        $search = new SortieSearch();
//        $search->setIsInscrit(true);
//
//        $sortieRepository = self::getContainer()->get(SortieRepository::class);
//        $results = $sortieRepository->findByFilters($search);
//
//        $this->assertNotEmpty($results);
//
//    }

    public function testFindByFiltersReturnsResultsWhenDateRangeMatches(): void
    {
        $search = new SortieSearch();
        $search->setStartDate(new \DateTime('-1 week'));
        $search->setEndDate(new \DateTime('+3 week'));

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        $this->assertNotEmpty($results);
    }

    public function testFindByFiltersReturnsResultsWhenCampusMatches(): void
    {
        $search = new SortieSearch();
        $search->setCampus($this->entityManager->getRepository(Participant::class)->findOneBy(['pseudo' => 'admin'])->getCampus());

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);
        $this->assertNotEmpty($results);
    }

    public function testFindByFiltersWhenIsOrganizer(): void
    {
        $userIds = $this->entityManager->getRepository(Participant::class)->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.pseudo != :adminPseudo')
            ->setParameter('adminPseudo', 'admin')
            ->getQuery()
            ->getArrayResult();

        // Select a random ID from the list
        $randomId = $userIds[array_rand($userIds)]['id'];

        // Retrieve the user with the selected random ID
        $user = $this->entityManager->getRepository(Participant::class)->find($randomId);

        // Set the user in the session
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        $this->session->set('_security_main', serialize($token));

        // Set the search criteria
        $search = new SortieSearch();
        $search->setIsOrganizer(true);

        // Retrieve the repository and execute the search
        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        // Assert that the results are not empty
        $this->assertNotEmpty($results);
    }


}