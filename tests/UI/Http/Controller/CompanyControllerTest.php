<?php

declare(strict_types=1);

namespace App\Tests\UI\Http\Controller;

use App\Application\Dto\CompanyDto;
use App\Application\Factory\CompanyDtoFactory;
use App\Domain\Entity\Company;
use App\Domain\Repository\CompanyRepository;
use App\Tests\BaseTestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

class CompanyControllerTest extends BaseTestController
{
    public function testOfTestInit(): void
    {
        self::assertTrue(true);
    }

    public function testCreateCompany(): void
    {
        $payload = $this->createCompany();

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $companies = $companyRepo->findBy(['email' => $payload['email']]);

        $this->assertCount(1, $companies);
        $this->assertSame($payload['longName'], $companies[0]->getLongName());
    }

    public function testEditCompany(): void
    {
        // Najpierw tworzymy firmę (wykorzystując helper)
        $payloadCreate = $this->createCompany();

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payloadCreate['email']]);

        $companyId = $company->getId();

        // Przygotowujemy payload do edycji, zmieniając np. email i nazwę
        $payloadEdit = [
            'email' => 'firma@example.com',
            'shortName' => 'ZZ',
            'longName' => 'Zmodyfikowana Sp. z o.o.',
            'taxNumber' => '1987654321',
            'country' => 'Polska',
            'city' => 'Warszawa',
            'postalCode' => '00-001',
            'street' => 'Nowa',
            'buildingNumber' => '1',
            'apartmentNumber' => 10,
            'isActive' => true,
        ];

        $this->request(
            'POST',
            '/api/company/edit/' . $companyId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payloadEdit)
        );

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame('ok', $data['saved'] ?? null);

        // Sprawdzenie czy edycja zadziałała
        $companyUpdated = $companyRepo->find($companyId);
        $this->assertSame('firma@example.com', $companyUpdated->getEmail());
        $this->assertSame('Zmodyfikowana Sp. z o.o.', $companyUpdated->getLongName());
        $this->assertSame('Warszawa', $companyUpdated->getCity());
        $this->assertTrue($companyUpdated->isActive());
    }

    public function testDeleteCompany(): void
    {
        $payloadCreate = $this->createCompany();

        $this->request(
            'POST',
            '/api/company/delete/' . 1,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payloadCreate['email']]);

        $this->assertFalse($company->isActive());
        $this->assertTrue($company->isDeleted());
    }

    public function testChangeActivityCompany(): void
    {
        $payloadCreate = $this->createCompany();

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payloadCreate['email']]);

        $this->assertTrue($company->isActive());

        $this->request(
            'POST',
            '/api/company/toggle-active/' . 1,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );


        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payloadCreate['email']]);

        $this->assertFalse($company->isActive());

        $this->request(
            'POST',
            '/api/company/toggle-active/' . 1,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $this->assertFalse($company->isActive());
    }

    public function testListCompanies(): void
    {
        // Utwórz dwie firmy
        $this->createCompany(['email' => 'firma1@example.com', 'shortName' => 'F1']);
        $this->createCompany(['email' => 'firma2@example.com', 'shortName' => 'F2']);

        // Wywołaj endpoint listy
        $this->request('GET', '/api/company/active');

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);

        // Sprawdź że to tablica i zawiera min. 2 elementy
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        // Sprawdź strukturę jednego elementu
        $company = $data[0];

        $this->assertArrayHasKey('id', $company);
        $this->assertArrayHasKey('email', $company);
        $this->assertArrayHasKey('shortName', $company);
        $this->assertArrayHasKey('isActive', $company);
    }

    public function testValidCompanyDtoPassesValidation(): void
    {
        $dto = new CompanyDto(
            null,
            null,
            'firma@example.com',
            'FN',
            'Firma Nowa',
            '1234567890',
            'Polska',
            'Warszawa',
            '00-001',
            'Ulica',
            '1a',
            1,
            true
        );

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors, (string)$errors);
    }

    public function testInvalidEmailFailsValidation(): void
    {
        $dto = new CompanyDto(
            null,
            null,
            'zly-email',
            'FN',
            'Firma Nowa',
            '1234567890',
            'Polska',
            'Warszawa',
            '00-001',
            'Ulica',
            '12a',
            1,
            true
        );

        $errors = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($errors));
    }

    public function testEmptyRequiredFieldsFailValidation(): void
    {
        $dto = new CompanyDto(
            null,
            null,
            '', // brak emaila
            '', // brak shortName
            '', // brak longName
            '', // brak taxNumber
            '', // brak country
            '', // brak city
            '', // brak postalCode
            '', // brak street
            '', // brak buildingNumber
            0,
            true
        );

        $errors = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($errors));
    }

    public function testCreateCompanyValidationFails(): void
    {
        $payload = [
            // brak wymaganych pól np. email, shortName, longName itd.
            'uuid' => Uuid::v4()->toRfc4122(),
            // email pominięty celowo
            'shortName' => '',
            'longName' => '',
            'taxNumber' => '',
            'country' => '',
            'city' => '',
            'postalCode' => '',
            'street' => '',
            'buildingNumber' => '',
            'apartmentNumber' => null,
            'isActive' => true,
        ];

        $this->request(
            'POST',
            '/api/company/new',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->response();
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testCreateCompanyFailsIfEmailExists(): void
    {
        // Utwórz pierwszą firmę - request POST
        $payload1 = [
            'uuid' => Uuid::v4()->toRfc4122(),
            'email' => 'duplicate@example.com',
            'shortName' => 'F1',
            'longName' => 'Firma 1',
            'taxNumber' => '1234567890',
            'country' => 'PL',
            'city' => 'Warszawa',
            'postalCode' => '00-001',
            'street' => 'Ulica',
            'buildingNumber' => '1',
            'apartmentNumber' => null,
            'isActive' => true,
        ];
        $this->request('POST', '/api/company/new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload1));
        $response1 = $this->response();
        $this->assertSame(200, $response1->getStatusCode());

        // Spróbuj utworzyć drugą firmę z tym samym emailem
        $payload2 = $payload1;
        $payload2['uuid'] = Uuid::v4()->toRfc4122(); // inny uuid, ale ten sam email

        $this->request('POST', '/api/company/new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload2));
        $response2 = $this->response();

        $this->assertSame(400, $response2->getStatusCode());
        $data = json_decode((string)$response2->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('email', $data['errors']);
    }


    public function testEditCompanyFailsIfShortNameExists(): void
    {
        // Tworzymy dwie firmy
        $this->createCompany(['email' => 'firma1@example.com', 'shortName' => 'F1']);
        $payload2 = $this->createCompany(['email' => 'firma2@example.com', 'shortName' => 'F2']);

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company2 = $companyRepo->findOneBy(['email' => $payload2['email']]);
        $company2Id = $company2->getId();

        // Próba edycji firmy 2, ustawiając shortName na "F1" (istniejący)
        $payloadEdit = [
            'uuid' => $company2->getUuid()->toRfc4122(),
            'email' => 'firma2@example.com',
            'shortName' => 'F1', // istniejący shortName
            'longName' => 'Firma 2 zmieniona',
            'taxNumber' => '1234567890',
            'country' => 'Polska',
            'city' => 'Miasto',
            'postalCode' => '00-000',
            'street' => 'Ulica',
            'buildingNumber' => '1',
            'apartmentNumber' => null,
            'isActive' => true,
        ];

        $this->request(
            'POST',
            '/api/company/edit/' . $company2Id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payloadEdit)
        );

        $response = $this->response();
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('krótkiej nazwie', $data['errors']);
    }

    public function testDeleteCompanyFailsIfNotFound(): void
    {
        $this->request(
            'POST',
            '/api/company/delete/999', // ID, którego nie ma
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('nie znaleziona', strtolower($data['errors']));
    }

    public function testChangeActiveFailsIfNotFound(): void
    {
        $this->request(
            'POST',
            '/api/company/toggle-active/999', // ID, którego nie ma
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('nie znaleziona', strtolower($data['errors']));
    }

    public function testFromRequestSuccess(): void
    {
        $factory = new CompanyDtoFactory();

        $payload = [
            'uuid' => Uuid::v4()->toRfc4122(),
            'email' => 'firma@example.com',
            'shortName' => 'FN',
            'longName' => 'Firma Nowa',
            'taxNumber' => '1234567890',
            'country' => 'Polska',
            'city' => 'Warszawa',
            'postalCode' => '00-001',
            'street' => 'Nowa',
            'buildingNumber' => '1',
            'apartmentNumber' => null,
            'isActive' => true,
        ];

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $dto = $factory->fromRequest($request);

        $this->assertSame($payload['email'], $dto->email);
        $this->assertSame($payload['shortName'], $dto->shortName);
        $this->assertTrue($dto->isActive);
    }

    public function testFromRequestThrowsExceptionIfMissingField(): void
    {
        $factory = new CompanyDtoFactory();

        // Brakuje "email"
        $payload = [
            'uuid' => Uuid::v4()->toRfc4122(),
            'shortName' => 'FN',
            'longName' => 'Firma Nowa',
            'taxNumber' => '1234567890',
            'country' => 'Polska',
            'city' => 'Warszawa',
            'postalCode' => '00-001',
            'street' => 'Nowa',
            'buildingNumber' => '1',
            'isActive' => true,
        ];

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Brak wymaganego pola: email');

        $factory->fromRequest($request);
    }

    public function testFromRequestThrowsExceptionIfInvalidJson(): void
    {
        $factory = new CompanyDtoFactory();

        // Niepoprawny JSON
        $request = new Request([], [], [], [], [], [], 'INVALID_JSON');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nieprawidłowe dane JSON w żądaniu');

        $factory->fromRequest($request);
    }

    public function testEditCompanyNotFound(): void
    {
        $payload = [
            'uuid' => Uuid::v4()->toRfc4122(),
            'email' => 'notfound@example.com',
            'shortName' => 'NF',
            'longName' => 'Not Found',
            'taxNumber' => '1111111111',
            'country' => 'Polska',
            'city' => 'Warszawa',
            'postalCode' => '00-001',
            'street' => 'Ulica',
            'buildingNumber' => '1',
            'apartmentNumber' => null,
            'isActive' => true,
        ];

        $this->request(
            'POST',
            '/api/company/edit/9999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testDeleteCompanyNotFound(): void
    {
        $this->request(
            'POST',
            '/api/company/delete/9999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testChangeActiveCompanyNotFound(): void
    {
        $this->request(
            'POST',
            '/api/company/toggle-active/9999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    private function getDto(): CompanyDto
    {
        return new CompanyDto(
            null,
            null,
            'test@example.com',
            'SHORT',
            'Long Name',
            '1234567890',
            'Polska',
            'Warszawa',
            '00-001',
            'Ulica',
            '1a',
            1,
            true
        );
    }

    public function testCreateCompanyThrowsIfEmailExists(): void
    {
        $this->repo->method('findOneBy')->willReturn(new Company());

        $this->expectException(\DomainException::class);
        $this->service->createCompany($this->getDto(), 1);
    }

    public function testCreateCompanyThrowsIfShortNameExists(): void
    {
        $this->repo->method('findOneBy')->willReturnOnConsecutiveCalls(null, new Company());

        $this->expectException(\DomainException::class);
        $this->service->createCompany($this->getDto(), 1);
    }

    public function testUpdateCompanyReturnsNullIfNotFound(): void
    {
        $this->repo->method('find')->willReturn(null);
        $result = $this->service->updateCompany($this->getDto(), 1);
        $this->assertNull($result);
    }

    public function testDeleteCompanyReturnsNullIfNotFound(): void
    {
        $this->repo->method('find')->willReturn(null);
        $result = $this->service->deleteCompany(999, 1);
        $this->assertNull($result);
    }

    public function testChangeActiveReturnsNullIfNotFound(): void
    {
        $this->repo->method('find')->willReturn(null);
        $result = $this->service->changeActive(999, 1);
        $this->assertNull($result);
    }

    public function testDeleteCompanyNotFound2(): void
    {
        $this->request(
            'POST',
            '/api/company/delete/9999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('Firma nie znaleziona', $data['errors']);
    }

    public function testChangeActiveCompanyNotFound2(): void
    {
        $this->request(
            'POST',
            '/api/company/toggle-active/9999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            null
        );

        $response = $this->response();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('Firma nie znaleziona', $data['errors']);
    }

    public function testReviewCompany(): void
    {
        $payload = $this->createCompany();

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payload['email']]);

        $this->request('GET', '/api/company/review/' . $company->getId());

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testDeletedCompanies(): void
    {
        $payload = $this->createCompany();

        $companyRepo = self::getContainer()->get(CompanyRepository::class);
        $company = $companyRepo->findOneBy(['email' => $payload['email']]);
        $company->setIsDeleted(true);
        self::getContainer()->get('doctrine')->getManager()->flush();

        $this->request('GET', '/api/company/deleted');

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertNotEmpty($data);
        $this->assertSame($payload['email'], $data[0]['email']);
    }

    public function testActiveCompanies(): void
    {
        $this->createCompany();

        $this->request('GET', '/api/company/active');

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('email', $data[0]);
    }

    public function testGetAllCompaniesActive(): void
    {
        $companyRepo = self::getContainer()->get(CompanyRepository::class);

        $this->createCompany(['email' => 'active@example.com', 'shortName' => 'test1']);
        $this->createCompany(['email' => 'deleted@example.com', 'isActive' => false, 'shortName' => 'test2']);

        $result = $companyRepo->getAllCompaniesActive();

        self::assertCount(2, $result);
        self::assertSame('active@example.com', $result[0]->getEmail());
    }

    public function testGetAllCompaniesDeleted(): void
    {
        $companyRepo = self::getContainer()->get(CompanyRepository::class);

        $this->createCompany(['email' => 'active@example.com']);

        // Usunięcie jednej firmy
        $company = $companyRepo->findOneBy(['email' => 'active@example.com']);
        $company->setIsDeleted(true);
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $result = $companyRepo->getAllCompaniesDeleted();

        self::assertCount(1, $result);
        self::assertSame('active@example.com', $result[0]->getEmail());
    }

    public function testGetCompany(): void
    {

        $companyRepo = self::getContainer()->get(CompanyRepository::class);

        $payload = $this->createCompany(['email' => 'findme@example.com']);
        $company = $companyRepo->findOneBy(['email' => $payload['email']]);
        $found = $companyRepo->getCompany($company->getId());

        self::assertInstanceOf(Company::class, $found);
        self::assertSame('findme@example.com', $found->getEmail());
    }

    private function createCompany(array $override = []): array
    {
        $payload = array_merge(
            [
                'email' => 'firma@example.com',
                'shortName' => 'TT',
                'longName' => 'Testowa Sp. z o.o.',
                'taxNumber' => '1234567890',
                'country' => 'Polska',
                'city' => 'Wrocław',
                'postalCode' => '50-001',
                'street' => 'Piłsudskiego',
                'buildingNumber' => '10A',
                'apartmentNumber' => 5,
                'isActive' => true,
            ],
            $override
        );

        $this->request(
            'POST',
            '/api/company/new',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->response();
        $this->assertSame(200, $response->getStatusCode());

        return $payload;
    }
}
