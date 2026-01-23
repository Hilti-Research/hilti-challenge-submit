<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TeamTrait;
use App\Entity\Traits\TimeTrait;
use App\Entity\Traits\UserTrait;
use App\Enum\ChallengeType;
use App\Enum\EvaluationStatus;
use App\Enum\EvaluationType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait;
    use TimeTrait;
    use UserTrait;
    use TeamTrait;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $preferAnonymity = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $hideFromLeaderboard = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAdmin = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $receiveAdminNotifications = false;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class, mappedBy: 'user', fetch: 'EAGER')]
    private Collection $submissions;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'sentBy')]
    private Collection $emails;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->emails = new ArrayCollection();
    }

    public const string ROLE_USER = 'ROLE_USER';
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    public function getRoles(): array
    {
        return [$this->isAdmin ? self::ROLE_ADMIN : self::ROLE_USER];
    }

    public function getPreferAnonymity(): bool
    {
        return $this->preferAnonymity;
    }

    public function setPreferAnonymity(bool $preferAnonymity): void
    {
        $this->preferAnonymity = $preferAnonymity;
    }

    public function isHideFromLeaderboard(): bool
    {
        return $this->hideFromLeaderboard;
    }

    public function setHideFromLeaderboard(bool $hideFromLeaderboard): void
    {
        $this->hideFromLeaderboard = $hideFromLeaderboard;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function getReceiveAdminNotifications(): bool
    {
        return $this->receiveAdminNotifications;
    }

    public function setReceiveAdminNotifications(bool $receiveAdminNotifications): void
    {
        $this->receiveAdminNotifications = $receiveAdminNotifications;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    /**
     * @return Submission[]
     */
    public function getFilteredSubmissions(ChallengeType $challengeType, ?EvaluationType $evaluationType = null, ?\DateTime $deadline = null): array
    {
        $submissions = [];
        foreach ($this->getSubmissions() as $submission) {
            if ($submission->getChallengeType() !== $challengeType) {
                continue;
            }

            if ($submission->getEvaluationType() !== $evaluationType) {
                continue;
            }

            if ($deadline && $submission->getCreatedAt() > $deadline) {
                continue;
            }

            $submissions[] = $submission;
        }

        return $submissions;
    }

    /**
     * @param Submission[] $submissions
     */
    public static function getBestSubmission(array $submissions): ?Submission
    {
        usort($submissions, fn (Submission $a, Submission $b): int => $a->getIteration() <=> $b->getIteration());

        /** @var Submission|null $bestSubmission */
        $bestSubmission = null;
        foreach ($submissions as $submission) {
            if (EvaluationStatus::SUCCESS !== $submission->getEvaluationStatus()) {
                continue;
            }

            if ($submission->isMostRecentEvaluationVersion()) {
                continue;
            }

            if (null === $bestSubmission || $bestSubmission->getEvaluationScore() <= $submission->getEvaluationScore()) {
                $bestSubmission = $submission;
            }
        }

        return $bestSubmission;
    }
}
