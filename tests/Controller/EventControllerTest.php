<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $eventRepository;
    private string $path = '/event/';

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->manager = self::getContainer()->get('doctrine')->getManager();
        $this->eventRepository = $this->manager->getRepository(Event::class);

        foreach ($this->eventRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Couch Gaming - Liste des événements');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(404);
    }

    public function testShow(): void
    {
        $fixture = new Event();
        $fixture->setName('My Title');
        $fixture->setDescription('Event description');
        $fixture->setDate(new \DateTime('2030-01-01T10:00:00+00:00'));
        $fixture->setImage('event.png');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('My Title - Événement');
        self::assertSelectorTextContains('h1.event-hero-title', 'My Title');
    }

    public function testEdit(): void
    {
        $this->client->request('GET', sprintf('%s1/edit', $this->path));

        self::assertResponseStatusCodeSame(404);
    }

    public function testRemove(): void
    {
        $this->client->request('DELETE', sprintf('%s1', $this->path));

        self::assertResponseStatusCodeSame(405);
    }
}
