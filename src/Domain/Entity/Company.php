<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Domain\Repository\CompanyRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(
    name: '`company`',
    indexes: [
        ['name' => 'idx_company_shortname', 'columns' => ['shortName']],
        ['name' => 'idx_company_uuid', 'columns' => ['uuid']]
    ]
)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private ?Uuid $uuid = null;
    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $email;
    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $shortName;
    #[ORM\Column(length: 255, nullable: false)]
    private string $longName;
    #[ORM\Column(length: 255, nullable: false)]
    private string $taxNumber;
    #[ORM\Column(length: 255, nullable: false)]
    private string $country;
    #[ORM\Column(length: 255, nullable: false)]
    private string $city;
    #[ORM\Column(length: 255, nullable: false)]
    private string $postalCode;
    #[ORM\Column(length: 255, nullable: false)]
    private string $street;
    #[ORM\Column(length: 255, nullable: false)]
    private string $buildingNumber;
    #[ORM\Column(nullable: true)]
    private ?int $apartmentNumber;
    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt;
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $createdBy;
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $updatedBy;
    #[ORM\Column]
    private bool $isActive;
    #[ORM\Column]
    private bool $isDeleted;
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;
    #[ORM\Column(type: 'boolean')]
    private bool $isSystem = false;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->createdAt = new DateTimeImmutable();
        $this->isDeleted = false;
        $this->isSystem = false;
    }

    public function activate(Uuid $adminUuid): void
    {
        $this->isActive = true;
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(Uuid $adminUuid): void
    {
        $this->isActive = false;
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function softDelete(Uuid $adminUuid): void
    {
        $this->isDeleted = true;
        $this->isActive = false;
        $this->deletedAt = new DateTimeImmutable();
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getLongName(): ?string
    {
        return $this->longName;
    }

    public function setLongName(string $longName): static
    {
        $this->longName = $longName;

        return $this;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(string $taxNumber): static
    {
        $this->taxNumber = $taxNumber;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    public function setBuildingNumber(string $buildingNumber): static
    {
        $this->buildingNumber = $buildingNumber;

        return $this;
    }

    public function getApartmentNumber(): ?int
    {
        return $this->apartmentNumber;
    }

    public function setApartmentNumber(?int $apartmentNumber): static
    {
        $this->apartmentNumber = $apartmentNumber;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?Uuid
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Uuid $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?Uuid
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?Uuid $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isSystem(): ?bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;

        return $this;
    }
}