<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\Factory\CompanyFactory;
use App\Application\Service\CompanyMailer;
use App\Application\Service\CompanyService;
use App\Domain\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseTestController extends WebTestCase
{
    protected KernelBrowser $client;
    protected ValidatorInterface $validator;
    protected CompanyRepository $repo;
    protected EntityManagerInterface $em;
    protected CompanyFactory $factory;
    protected CompanyService $service;
    protected MessageBusInterface $messageBus;
    protected CompanyMailer $companyMailer;

    protected function setUp(): void
    {
        $this->setUpClient();
        $container = static::getContainer();
        $connection = $container->get('database_connection');
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->repo = $this->createMock(CompanyRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->factory = $this->createMock(CompanyFactory::class);
        // Zamockowany MessageBus w kontenerze
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->client->getContainer()->set(MessageBusInterface::class, $this->messageBus);
        $this->client->disableReboot();
        
        $this->companyMailer = $this->createMock(CompanyMailer::class);
        $this->service = new CompanyService($this->repo, $this->em, $this->factory, $this->companyMailer);
        // Przywróć stan bazy
        $connection->executeStatement('TRUNCATE TABLE "company" RESTART IDENTITY CASCADE');
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown(); // zakończ kernel żeby nie było błędów w kolejnych testach
        parent::tearDown();
    }

    protected function response(): ?Response
    {
        return $this->client->getResponse();
    }

    protected function setUpClient(): void
    {
        self::ensureKernelShutdown();
        parent::setUp();
        $this->client = static::createClient();

        $this->client->disableReboot();
    }

    protected function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null): void
    {
        $this->client->request($method, $uri, $parameters, $files, $server, $content);
    }
}
