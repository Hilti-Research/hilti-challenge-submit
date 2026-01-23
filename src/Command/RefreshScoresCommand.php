<?php

namespace App\Command;

use App\Entity\Submission;
use App\Entity\User;
use App\Enum\EvaluationStatus;
use App\Service\EvaluationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:evaluation:resubmit', description: 'Reexecutes the evaluations of an outdated evaluation version')]
class RefreshScoresCommand extends Command
{
    use LockableTrait;

    public function __construct(protected ManagerRegistry $manager, private EvaluationService $evaluationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('challenge', InputArgument::REQUIRED, 'The challenge to reconsider.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

        $submissions = $this->findSubmissions($input);
        $output->writeln('Found ' . count($submissions) . ' submissions to reevaluate.');

        // increase max execution time relative to submission count
        $currentMaxExecutionTime = ini_get('max_execution_time');
        $newMaxExecutionTime = (int) ($currentMaxExecutionTime + count($submissions) * 1.5);
        ini_set('max_execution_time', $newMaxExecutionTime);

        $submissionsProcessed = 0;
        foreach ($submissions as $submission) {
            $this->evaluationService->startEvaluation($submission);
            $output->writeln('Processed (' . ++$submissionsProcessed . '/' . count($submissions) . ') submissions.');

            sleep(1);
        }

        $this->release();

        $output->writeln('Finished.');

        return Command::SUCCESS;
    }

    protected function findSubmissions(InputInterface $input): array
    {
        $challenge = $input->getArgument('challenge');

        $userRepository = $this->manager->getRepository(User::class);
        $users = $userRepository->findAll();
        $result = [];
        foreach ($users as $user) {
            $result = \array_merge($result, $user->getFilteredSubmissions($challenge));
        }

        return \array_filter($result, function (Submission $entry): bool {
            return $entry->isMostRecentEvaluationVersion();
        });
    }
}
