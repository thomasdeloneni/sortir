<?php

namespace App\Tests\Repository;

use App\DataFixtures\AppFixtures;
use App\Entity\Participant;
use App\Form\model\SortieSearch;
use App\Repository\SortieRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SortieRepositoryTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $userPasswordHasher;
//    private SessionInterface $session;
    private KernelBrowser $client;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

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

        foreach ($results as $result) {
            $this->assertEquals($search->getCampus(), $result->getCampus());
        }
    }


    public function testFindByFiltersWhenIsOrganizer(): void
    {
        $userIds = $this->entityManager->getRepository(Participant::class)->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.pseudo != :admin')
            ->setParameter('admin', 'admin')
            ->getQuery()
            ->getArrayResult();

        $randomId = $userIds[array_rand($userIds)]['id'];

        $user = $this->entityManager->getRepository(Participant::class)->find($randomId);

        $this->client->loginUser($user);

        $search = new SortieSearch();
        $search->setIsOrganizer(true);

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertEquals($user, $result->getOrganisateur());
        }

    }

    public function testFindByFiltersWhenIsRegistered(): void
    {
        $userIds = $this->entityManager->getRepository(Participant::class)->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.pseudo != :admin')
            ->setParameter('admin', 'admin')
            ->getQuery()
            ->getArrayResult();

        $randomId = $userIds[array_rand($userIds)]['id'];

        $user = $this->entityManager->getRepository(Participant::class)->find($randomId);

        $this->client->loginUser($user);

        $search = new SortieSearch();
        $search->setIsInscrit(true);

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertContains($user, $result->getParticipant());
        }
    }

    public function testFindByFiltersWhenIsNotRegistered(): void
    {
        $userIds = $this->entityManager->getRepository(Participant::class)->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.pseudo != :admin')
            ->setParameter('admin', 'admin')
            ->getQuery()
            ->getArrayResult();

        $randomId = $userIds[array_rand($userIds)]['id'];

        $user = $this->entityManager->getRepository(Participant::class)->find($randomId);

        $this->client->loginUser($user);

        $search = new SortieSearch();
        $search->setIsNotInscrit(true);

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertNotContains($user, $result->getParticipant());
        }
    }

    public function testFindByFiltersWhenIsFinished(): void
    {
        $search = new SortieSearch();
        $search->setIsFinished(true);

        $sortieRepository = self::getContainer()->get(SortieRepository::class);
        $results = $sortieRepository->findByFilters($search);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertEquals('Passée', $result->getEtat()->getLibelle());
        }
    }
}