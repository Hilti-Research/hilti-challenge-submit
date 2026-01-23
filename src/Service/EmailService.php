<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Email;
use App\Entity\Submission;
use App\Entity\User;
use App\Enum\EmailType;
use App\Helper\DoctrineHelper;
use App\Service\Interfaces\EmailServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class EmailService implements EmailServiceInterface
{
    public function __construct(private TranslatorInterface $translator, private LoggerInterface $logger, private ManagerRegistry $registry, private MailerInterface $mailer, private string $mailerFromEmail, private string $supportEmail, private RouterInterface $router, private ManagerRegistry $managerRegistry)
    {
    }

    public function sendRecoverConfirmLink(User $user): bool
    {
        $link = $this->router->generate('recover_confirm', ['authenticationHash' => $user->getAuthenticationHash()]);
        $entity = Email::create(EmailType::RECOVER_CONFIRM, $user, $link);
        $subject = $this->translator->trans('recover_confirm.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($user)
            ->subject($subject)
            ->textTemplate('email/recover_confirm.txt.twig')
            ->htmlTemplate('email/recover_confirm.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function notifySubmissionEvaluated(Submission $submission): bool
    {
        if (!$this->sendSubmissionEvaluatedToUser($submission)) {
            return false;
        }

        $admins = $this->managerRegistry->getRepository(User::class)->findBy(['isAdmin' => true, 'receiveAdminNotifications' => true]);
        if (count($admins) > 0) {
            $this->sendSubmissionEvaluatedToAdmin($submission, $admins);
        }

        return true;
    }

    private function sendSubmissionEvaluatedToAdmin(Submission $submission, array $admins): void
    {
        $user = $submission->getUser();

        $score = $submission->getEvaluationScore();
        $body = ['score' => $score, 'team' => $user->getTeamName()];

        $link = $this->router->generate('submission_mine', ['_switch_user' => $user->getEmail()]);
        $entity = Email::create(EmailType::SUBMISSION_EVALUATED_ADMIN_NOTIFICATION, $user, $link, $body);
        $subject = $this->translator->trans('submission_evaluated_admin_notification.subject', ['%team%' => $user->getTeamName(), '%score%' => round($score ?? 0, 2)], 'email');

        $message = $this->createTemplatedEmail()
            ->subject($subject)
            ->textTemplate('email/submission_evaluated_admin_notification.txt.twig')
            ->htmlTemplate('email/submission_evaluated_admin_notification.html.twig')
            ->context($entity->getContext());

        foreach ($admins as $admin) {
            $message->addTo($admin->getEmail());
        }
        dump($admins);

        $this->sendAndStoreEMail($message, $entity);
    }

    private function sendSubmissionEvaluatedToUser(Submission $submission): bool
    {
        $score = $submission->getEvaluationScore();
        $body = ['score' => $score];

        $link = $this->router->generate('submission_mine');
        $entity = Email::create(EmailType::SUBMISSION_EVALUATED, $submission->getUser(), $link, $body);
        $subject = $this->translator->trans('submission_evaluated.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($submission->getUser())
            ->subject($subject)
            ->textTemplate('email/submission_evaluated.txt.twig')
            ->htmlTemplate('email/submission_evaluated.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function notifySubmissionFailed(Submission $submission): bool
    {
        $link = $this->router->generate('submission_mine');
        $body = ['error' => $submission->getEvaluationError()];

        $entity = Email::create(EmailType::SUBMISSION_EVALUATION_FAILED, $submission->getUser(), $link, $body);
        $subject = $this->translator->trans('submission_evaluation_failed.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($submission->getUser())
            ->subject($subject)
            ->textTemplate('email/submission_evaluation_failed.txt.twig')
            ->htmlTemplate('email/submission_evaluation_failed.html.twig')
            ->attach($submission->getEvaluationErrorLog(), 'errors.txt')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    private function createTemplatedEmailToUser(User $user): TemplatedEmail
    {
        return $this->createTemplatedEmail()
            ->to($user->getEmail());
    }

    private function createTemplatedEmail(): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from($this->mailerFromEmail)
            ->replyTo($this->supportEmail)
            ->returnPath($this->supportEmail);
    }

    private function getCurrentPage(): string
    {
        return $this->router->getContext()->getHost();
    }

    private function sendAndStoreEMail(TemplatedEmail $email, Email $entity): bool
    {
        try {
            $this->mailer->send($email);

            DoctrineHelper::persistAndFlush($this->registry, $entity);

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('email send failed', ['exception' => $exception, 'email' => $entity]);

            return false;
        }
    }
}
