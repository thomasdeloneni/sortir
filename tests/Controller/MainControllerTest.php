<?php

namespace App\Tests\Controller;

use App\Entity\Participant;
use App\Form\model\SortieSearch;
use App\Repository\SortieRepository;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class MainControllerTest extends WebTestCase
{
    private KernelBrowser|null $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexIfNotConnected(): void
    {
        $urlGenerator = $this->client->getContainer()->get('router.default');
        $url = $urlGenerator->generate('app_main');

        $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseRedirects('/login');
    }

    public function testIndexWithConnectedUser(): void
    {
        $this->client->loginUser($this->client->getContainer()->get('doctrine')->getRepository(Participant::class)->findOneBy(['pseudo' => 'admin']));

        $urlGenerator = $this->client->getContainer()->get('router.default');
        $url = $urlGenerator->generate('app_main');

        $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithConnectedUserAndLogout(): void
    {
        $this->client->loginUser($this->client->getContainer()->get('doctrine')->getRepository(Participant::class)->findOneBy(['pseudo' => 'admin']));

        $urlGenerator = $this->client->getContainer()->get('router.default');
        $url = $urlGenerator->generate('app_main');

        $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseIsSuccessful();

        $url = $urlGenerator->generate('app_logout');

        $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseRedirects('/login');
    }

//    /**
//     * @throws Exception
//     */
//    public function testIndexWithFormSubmission(): void
//    {
//        $urlGenerator = self::getContainer()->get('router.default');
//        $url = $urlGenerator->generate('app_main');
//
//        // Mock the SortieRepository
//        $sortieRepository = $this->createMock(SortieRepository::class);
//        $sortieRepository->expects($this->once())
//            ->method('findByFilters')
//            ->with($this->isInstanceOf(SortieSearch::class))
//            ->willReturn(['test']);
//
//        // Replace the real repository with the mock
//        self::getContainer()->set('App\Repository\SortieRepository', $sortieRepository);
//
//        // Simulate form submission
//        $crawler = $this->client->request(Request::METHOD_GET, $url);
//        $form = $crawler->selectButton('Submit')->form([
//            'sortie_filter[campus.name]' => 'Saint-Herblain', // Replace with actual form field names and values
//        ]);
//
//        $this->client->submit($form);
//
//        // Assert that the response is successful
//        $this->assertResponseIsSuccessful();
//    }

}