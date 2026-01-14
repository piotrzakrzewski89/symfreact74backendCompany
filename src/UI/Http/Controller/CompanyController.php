<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Dto\CompanyDto;
use App\Domain\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Application\Factory\CompanyDtoFactory;
use App\Application\Service\CompanyService;

#[Route('/api/company', name: 'api_company_')]
class CompanyController
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private CompanyService $companyService,
        private CompanyDtoFactory $companyDtoFactory,
        private ValidatorInterface $validator
    ) {}

    #[Route('/review/{id}', name: 'review', methods: ['GET'])]
    public function review(int $id): JsonResponse
    {
        return new JsonResponse(CompanyDto::fromEntity($this->companyRepository->getCompany($id)));
    }

    #[Route('/deleted', name: 'deleted', methods: ['GET'])]
    public function deleted(): JsonResponse
    {
        return new JsonResponse(CompanyDto::fromEntities($this->companyRepository->getAllCompaniesDeleted()));
    }

    #[Route('/active', name: 'active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        return new JsonResponse(CompanyDto::fromEntities($this->companyRepository->getAllCompaniesActive()));
    }

    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $dto = $this->companyDtoFactory->fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $company = $this->companyService->createCompany($dto, 1);
        } catch (\DomainException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        return new JsonResponse(['saved' => 'ok', 'id' => $company->getId()]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            $dto = $this->companyDtoFactory->fromRequest($request);
            $dto->id = $id;
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $company = $this->companyService->updateCompany($dto, 1);
        } catch (\DomainException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        if (!$company) {
            return new JsonResponse(['errors' => 'Firma nie znaleziona'], 404);
        }

        return new JsonResponse(['saved' => 'ok', 'id' => $company->getId()]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $company = $this->companyService->deleteCompany($id, 1);

        if (!$company) {
            return new JsonResponse(['errors' => 'Firma nie znaleziona'], 404);
        }

        return new JsonResponse(['api_company_delete' => 'ok'], 200);
    }

    #[Route('/toggle-active/{id}', name: 'toggle-active', methods: ['POST'])]
    public function changeActive(int $id): JsonResponse
    {
        $company = $this->companyService->changeActive($id, 1);

        if (!$company) {
            return new JsonResponse(['errors' => 'Firma nie znaleziona'], 404);
        }

        return new JsonResponse(['api_company_change' => 'ok'], 200);
    }

    #[Route('/company-list-form', name: 'company-list-form-for-user', methods: ['GET'])]
    public function companyListForm(): JsonResponse
    {
        return new JsonResponse(CompanyDto::fromEntitiesForm($this->companyRepository->getAllCompaniesActive()));
    }
}
