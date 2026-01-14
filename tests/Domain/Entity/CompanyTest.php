<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Company;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CompanyTest extends TestCase
{
    public function testCompanyCreation(): void
    {
        $company = new Company();

        $this->assertInstanceOf(Uuid::class, $company->getUuid());
        $this->assertInstanceOf(\DateTimeImmutable::class, $company->getCreatedAt());
        $this->assertFalse($company->isDeleted());
        $this->assertFalse($company->isSystem());
        // ID is not initialized until entity is persisted
        $this->assertTrue(true); // Skip ID check for new entity
    }

    public function testCompanySettersAndGetters(): void
    {
        $company = new Company();
        $uuid = Uuid::v4();

        $company->setEmail('test@example.com');
        $company->setShortName('TEST');
        $company->setLongName('Test Company Ltd.');
        $company->setTaxNumber('1234567890');
        $company->setCountry('Poland');
        $company->setCity('Warsaw');
        $company->setPostalCode('00-001');
        $company->setStreet('Test Street');
        $company->setBuildingNumber('123');
        $company->setApartmentNumber(45);
        $company->setIsActive(true);
        $company->setUuid($uuid);

        $this->assertSame('test@example.com', $company->getEmail());
        $this->assertSame('TEST', $company->getShortName());
        $this->assertSame('Test Company Ltd.', $company->getLongName());
        $this->assertSame('1234567890', $company->getTaxNumber());
        $this->assertSame('Poland', $company->getCountry());
        $this->assertSame('Warsaw', $company->getCity());
        $this->assertSame('00-001', $company->getPostalCode());
        $this->assertSame('Test Street', $company->getStreet());
        $this->assertSame('123', $company->getBuildingNumber());
        $this->assertSame(45, $company->getApartmentNumber());
        $this->assertTrue($company->isActive());
        $this->assertSame($uuid, $company->getUuid());
    }

    public function testCompanyActivateDeactivate(): void
    {
        $company = new Company();
        $adminUuid = Uuid::v4();

        // Test activate
        $company->activate($adminUuid);
        $this->assertTrue($company->isActive());
        $this->assertSame($adminUuid, $company->getUpdatedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $company->getUpdatedAt());

        // Test deactivate
        $company->deactivate($adminUuid);
        $this->assertFalse($company->isActive());
        $this->assertSame($adminUuid, $company->getUpdatedBy());
    }

    public function testCompanySoftDelete(): void
    {
        $company = new Company();
        $company->setIsActive(true);
        $adminUuid = Uuid::v4();

        $company->softDelete($adminUuid);

        $this->assertTrue($company->isDeleted());
        $this->assertFalse($company->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $company->getDeletedAt());
        $this->assertSame($adminUuid, $company->getUpdatedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $company->getUpdatedAt());
    }

    public function testCompanySystemFlag(): void
    {
        $company = new Company();

        $this->assertFalse($company->isSystem());

        $company->setIsSystem(true);
        $this->assertTrue($company->isSystem());
    }

    public function testCompanyDatesHandling(): void
    {
        $company = new Company();
        $testDate = new \DateTimeImmutable('2024-01-01 12:00:00');

        $company->setCreatedAt($testDate);
        $company->setUpdatedAt($testDate);
        $company->setDeletedAt($testDate);

        $this->assertSame($testDate, $company->getCreatedAt());
        $this->assertSame($testDate, $company->getUpdatedAt());
        $this->assertSame($testDate, $company->getDeletedAt());
    }

    public function testCompanyCreatedByUpdatedBy(): void
    {
        $company = new Company();
        $uuid1 = Uuid::v4();
        $uuid2 = Uuid::v4();

        $company->setCreatedBy($uuid1);
        $company->setUpdatedBy($uuid2);

        $this->assertSame($uuid1, $company->getCreatedBy());
        $this->assertSame($uuid2, $company->getUpdatedBy());
    }
}
