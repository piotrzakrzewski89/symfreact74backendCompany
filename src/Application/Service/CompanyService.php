<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\CompanyDto;
use App\Application\Factory\CompanyFactory;
use App\Domain\Entity\Company;
use App\Domain\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class CompanyService
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private EntityManagerInterface $em,
        private CompanyFactory $companyFactory,
        private CompanyMailer $companyMailer
    ) {}

    public function createCompany(CompanyDto $dto, int $adminId): Company
    {
        // Sprawdzenie unikalności email
        if ($this->companyRepository->findOneBy(['email' => $dto->email])) {
            throw new \DomainException('Firma o tym adresie email już istnieje.');
        }

        // Sprawdzenie unikalności shortName
        if ($this->companyRepository->findOneBy(['shortName' => $dto->shortName])) {
            throw new \DomainException('Firma o tej krótkiej nazwie już istnieje.');
        }

        $adminUuid = is_string($adminId) ? Uuid::fromString($adminId) : Uuid::v4();
        $company = $this->companyFactory->createFromDto($dto, $adminUuid);

        $this->em->persist($company);
        $this->em->flush();

        $this->companyMailer->sendCreated($company);

        return $company;
    }

    public function updateCompany(CompanyDto $dto, int $adminId): ?Company
    {
        $company = $this->companyRepository->find($dto->id);

        if (!$company) {
            return null;
        }

        // Sprawdzenie unikalności email - tylko jeśli zmieniono lub nowy email
        $existingByEmail = $this->companyRepository->findOneBy(['email' => $dto->email]);
        if ($existingByEmail && $existingByEmail->getId() !== $company->getId()) {
            throw new \DomainException('Firma o tym adresie email już istnieje.');
        }

        // Sprawdzenie unikalności shortName
        $existingByShortName = $this->companyRepository->findOneBy(['shortName' => $dto->shortName]);
        if ($existingByShortName && $existingByShortName->getId() !== $company->getId()) {
            throw new \DomainException('Firma o tej krótkiej nazwie już istnieje.');
        }

        $adminUuid = is_string($adminId) ? Uuid::fromString($adminId) : Uuid::v4();
        $company = $this->companyFactory->updateFromDto($dto, $company, $adminUuid);

        $this->em->persist($company);
        $this->em->flush();

        $this->companyMailer->sendUpdated($company);

        return $company;
    }

    public function changeActive(int $id, int $adminId): ?Company
    {
        $company = $this->companyRepository->find($id);
        if (!$company) {
            return null;
        }

        $adminUuid = is_string($adminId) ? Uuid::fromString($adminId) : Uuid::v4();

        if ($company->isActive()) {
            $company->deactivate($adminUuid);
        } else {
            $company->activate($adminUuid);
        }

        $this->em->flush();

        $this->companyMailer->sendChangeActive($company);

        return $company;
    }

    public function deleteCompany(int $id, int $adminId): ?Company
    {
        $company = $this->companyRepository->find($id);
        if (!$company) {
            return null;
        }

        $adminUuid = is_string($adminId) ? Uuid::fromString($adminId) : Uuid::v4();
        $company->softDelete($adminUuid);

        $this->em->flush();

        $this->companyMailer->sendDeleted($company);

        return $company;
    }

}
