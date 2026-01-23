<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TeamTrait
{
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $teamName;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $affiliation;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $contactName;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $contactEmail;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $webpage;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $notes;

    public function getTeamName(): ?string
    {
        return $this->teamName;
    }

    public function setTeamName(?string $teamName): void
    {
        $this->teamName = $teamName;
    }

    public function getAffiliation(): ?string
    {
        return $this->affiliation;
    }

    public function setAffiliation(?string $affiliation): void
    {
        $this->affiliation = $affiliation;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getWebpage(): ?string
    {
        return $this->webpage;
    }

    public function setWebpage(?string $webpage): void
    {
        $this->webpage = $webpage;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function hasTeamSetupCompleted(): bool
    {
        return $this->teamName && $this->affiliation;
    }
}
