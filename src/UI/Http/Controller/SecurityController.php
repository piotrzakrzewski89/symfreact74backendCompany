<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company/security', name: 'api_company_security_')]
class SecurityController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/check-company', name: 'check_company', methods: ['POST'])]
    public function checkCompany(Request $request): JsonResponse
    {
      
        $data = json_decode($request->getContent(), true);
        $company = $this->em->getRepository(Company::class)->findOneBy(['shortName' => $data['companyShortName']]);

        return new JsonResponse([
            'companyShortName' => $company->getShortName(),
            'companyUuid' => $company->getUuid()
        ]);
    }
}
