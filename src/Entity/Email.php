<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Enum\EmailType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Email
{
    use IdTrait;

    #[ORM\Column(type: Types::GUID)]
    private string $identifier;

    #[ORM\Column(type: Types::STRING, enumType: EmailType::class)]
    private EmailType $type;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $link;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $body;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $sentAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'emails')]
    private User $sentBy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $readAt = null;

    public static function create(EmailType $type, User $sentBy, ?string $link, mixed $body = null): Email
    {
        $email = new Email();

        $email->identifier = UuidV4::v4();
        $email->type = $type;
        $email->link = $link;
        $email->body = $body;
        $email->sentBy = $sentBy;
        $email->sentAt = new \DateTime();

        return $email;
    }

    public function markRead(): void
    {
        $this->readAt = new \DateTime();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): EmailType
    {
        return $this->type;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function getSentAt(): \DateTime
    {
        return $this->sentAt;
    }

    public function getSentBy(): User
    {
        return $this->sentBy;
    }

    public function getReadAt(): ?\DateTime
    {
        return $this->readAt;
    }

    #[ArrayShape(['sentBy' => "\App\Entity\User", 'identifier' => 'string', 'emailType' => 'int', 'link' => 'null|string', 'body' => 'null|string'])]
    public function getContext(): array
    {
        return ['sentBy' => $this->sentBy, 'identifier' => $this->identifier, 'emailType' => $this->type, 'link' => $this->link, 'body' => $this->body];
    }
}
