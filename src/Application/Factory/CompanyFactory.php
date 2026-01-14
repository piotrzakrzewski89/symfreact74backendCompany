<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Application\Dto\CompanyDto;
use App\Domain\Entity\Company;
use Symfony\Component\Uid\Uuid;

class CompanyFactory
{
    public function createFromDto(CompanyDto $dto, Uuid $adminUuid): Company
    {
        $company = new Company();
        $this->mapDtoToEntity($dto, $company);
        $company->setIsDeleted(false);
        $company->setCreatedBy($adminUuid);

        return $company;
    }

    public function updateFromDto(CompanyDto $dto, Company $company, Uuid $adminUuid): Company
    {
        $this->mapDtoToEntity($dto, $company);
        $company->setIsDeleted(false);
        $company->setUpdatedBy($adminUuid);

        return $company;
    }

    private function mapDtoToEntity(CompanyDto $dto, Company $company): void
    {
        $company
            ->setEmail($dto->email)
            ->setShortName($dto->shortName)
            ->setLongName($dto->longName)
            ->setTaxNumber($dto->taxNumber)
            ->setCountry($dto->country)
            ->setCity($dto->city)
            ->setPostalCode($dto->postalCode)
            ->setStreet($dto->street)
            ->setBuildingNumber($dto->buildingNumber)
            ->setApartmentNumber($dto->apartmentNumber)
            ->setIsActive($dto->isActive);
    }
}
