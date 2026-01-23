<?php

namespace App\Repository;

use App\Entity\Submission;
use App\Enum\ChallengeType;
use App\Enum\EvaluationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Submission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Submission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Submission[]    findAll()
 * @method Submission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\Submission>
 */
class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function findParticipationTotals(ChallengeType $challengeType): array
    {
        $totalsPerEvaluation = [];

        foreach (EvaluationType::cases() as $evaluationType) {
            $result = $this->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->where('s.challengeType = :challengeType')
                ->setParameter(':challengeType', $challengeType)
                ->where('s.evaluationType = :evaluationType')
                ->setParameter(':evaluationType', $evaluationType)
                ->join('s.user', 'u')
                ->andWhere('u.hideFromLeaderboard = :hideFromLeaderboard')
                ->setParameter(':hideFromLeaderboard', false)
                ->addGroupBy('s.user')
                ->getQuery()
                ->getResult();

            $teams = 0;
            $submissions = 0;
            foreach ($result as $entry) {
                ++$teams;
                $submissions += $entry[1];
            }

            $totalsPerEvaluation[$evaluationType->value] = ['teams' => $teams, 'submissions' => $submissions];
        }

        return $totalsPerEvaluation;
    }
}
