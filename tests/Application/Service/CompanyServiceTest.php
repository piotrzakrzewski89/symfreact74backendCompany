<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Dto\CompanyDto;
use App\Application\Factory\CompanyFactory;
use App\Application\Service\CompanyMailer;
use App\Application\Service\CompanyService;
use App\Domain\Entity\Company;
use App\Domain\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CompanyServiceTest extends TestCase
{
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $em;
    private CompanyFactory $companyFactory;
    private CompanyMailer $companyMailer;
    private CompanyService $companyService;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->companyFactory = $this->createMock(CompanyFactory::class);
        $this->companyMailer = $this->createMock(CompanyMailer::class);
        
        $this->companyService = new CompanyService(
            $this->companyRepository,
            $this->em,
            $this->companyFactory,
            $this->companyMailer
        );
    }

    public function testCreateCompanySuccess(): void
    {
        $dto = $this->createCompanyDto();
        $company = new Company();
        $adminId = 1;

        $this->companyRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->companyFactory
            ->expects($this->once())
            ->method('createFromDto')
            ->with($dto, $this->isInstanceOf(Uuid::class))
            ->willReturn($company);

        $this->em->expects($this->once())->method('persist')->with($company);
        $this->em->expects($this->once())->method('flush');

        $this->companyMailer->expects($this->once())->method('sendCreated')->with($company);

        $result = $this->companyService->createCompany($dto, $adminId);

        $this->assertSame($company, $result);
    }

    public function testCreateCompanyThrowsExceptionWhenEmailExists(): void
    {
        $dto = $this->createCompanyDto();
        $existingCompany = new Company();

        $this->companyRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $dto->email])
            ->willReturn($existingCompany);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Firma o tym adresie email już istnieje.');

        $this->companyService->createCompany($dto, 1);
    }

    public function testCreateCompanyThrowsExceptionWhenShortNameExists(): void
    {
        $dto = $this->createCompanyDto();
        $existingCompany = new Company();

        $this->companyRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(null, $existingCompany);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Firma o tej krótkiej nazwie już istnieje.');

        $this->companyService->createCompany($dto, 1);
    }

    public function testUpdateCompanySuccess(): void
    {
        $dto = $this->createCompanyDto();
        $dto->id = 1;
        $company = new Company();
        $company->setEmail('old@example.com');

        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($dto->id)
            ->willReturn($company);

        $this->companyRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->companyFactory
            ->expects($this->once())
            ->method('updateFromDto')
            ->with($dto, $company, $this->isInstanceOf(Uuid::class))
            ->willReturn($company);

        $this->em->expects($this->once())->method('persist')->with($company);
        $this->em->expects($this->once())->method('flush');

        $this->companyMailer->expects($this->once())->method('sendUpdated')->with($company);

        $result = $this->companyService->updateCompany($dto, 1);

        $this->assertSame($company, $result);
    }

    public function testUpdateCompanyReturnsNullWhenNotFound(): void
    {
        $dto = $this->createCompanyDto();
        $dto->id = 999;

        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($dto->id)
            ->willReturn(null);

        $result = $this->companyService->updateCompany($dto, 1);

        $this->assertNull($result);
    }

    public function testDeleteCompanySuccess(): void
    {
        $companyId = 1;
        $adminId = 1;
        $company = new Company();

        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($companyId)
            ->willReturn($company);

        $this->em->expects($this->once())->method('flush');
        $this->companyMailer->expects($this->once())->method('sendDeleted')->with($company);

        $result = $this->companyService->deleteCompany($companyId, $adminId);

        $this->assertSame($company, $result);
    }

    public function testChangeActiveSuccess(): void
    {
        $companyId = 1;
        $adminId = 1;
        $company = new Company();
        $company->setIsActive(true);

        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($companyId)
            ->willReturn($company);

        $this->em->expects($this->once())->method('flush');
        $this->companyMailer->expects($this->once())->method('sendChangeActive')->with($company);

        $result = $this->companyService->changeActive($companyId, $adminId);

        $this->assertSame($company, $result);
    }

    private function createCompanyDto(): CompanyDto
    {
        return new CompanyDto(
            null,
            null,
            'test@example.com',
            'TEST',
            'Test Company',
            '1234567890',
            'Poland',
            'Warsaw',
            '00-001',
            'Test Street',
            '1',
            null,
            true
        );
    }
}
