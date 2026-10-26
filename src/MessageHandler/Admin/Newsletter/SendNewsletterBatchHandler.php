<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Newsletter;

use CeskaKruta\Web\Message\Admin\Newsletter\SendNewsletterBatch;
use CeskaKruta\Web\Query\Admin\GetPendingNewsletters;
use CeskaKruta\Web\Repository\ActualityNewsletterRepository;
use CeskaKruta\Web\Value\Admin\NewsletterBatchResult;
use CeskaKruta\Web\Value\Admin\PendingNewsletter;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Same pace as the old cron (PLAN §2.5, §3.8): fill up to 50 missing subscribers, send 10 mails per run.
 */
#[AsMessageHandler]
readonly final class SendNewsletterBatchHandler
{
    private const FILL_LIMIT = 50;
    private const BATCH_SIZE = 10;
    // Unsubscribe link stays on the production domain whatever host runs the cron (PLAN §6, Phase 1 review 7)
    private const WEB_URL = 'https://ceskakruta.cz';

    public function __construct(
        private ActualityNewsletterRepository $repository,
        private GetPendingNewsletters $getPendingNewsletters,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNewsletterBatch $message): NewsletterBatchResult
    {
        $queued = $this->repository->fillMissingForLatestActuality(self::FILL_LIMIT);
        $this->logger->info('Newsletter: added missing mails ({count})', ['count' => $queued]);

        $sent = [];

        foreach ($this->getPendingNewsletters->next(self::BATCH_SIZE) as $newsletter) {
            try {
                $email = $this->createEmail($newsletter);
            } catch (RfcComplianceException|InvalidParameterException $exception) {
                // Never sendable, must not block the queue (PLAN §6, Phase 1 review 4)
                $this->repository->markProcessed($newsletter->id, 'cron:invalid-email');
                $this->logger->warning('Newsletter: skipped invalid e-mail ({actuality_id}) {email}', [
                    'actuality_id' => $newsletter->actualityId,
                    'email' => $newsletter->email,
                    'exception' => $exception,
                ]);

                continue;
            }

            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $exception) {
                // Stop here, returning keeps the rows sent so far marked (throwing would roll them back → double send)
                $this->logger->error('Newsletter: sending failed ({actuality_id}) {email}', [
                    'actuality_id' => $newsletter->actualityId,
                    'email' => $newsletter->email,
                    'exception' => $exception,
                ]);

                return new NewsletterBatchResult($queued, $sent, $exception->getMessage());
            }

            $this->repository->markProcessed($newsletter->id, 'cron');
            $this->logger->info('Newsletter: success ({actuality_id}) {email}', [
                'actuality_id' => $newsletter->actualityId,
                'email' => $newsletter->email,
            ]);

            $sent[] = $newsletter->id;
        }

        return new NewsletterBatchResult($queued, $sent);
    }

    /**
     * @throws RfcComplianceException
     * @throws InvalidParameterException
     */
    private function createEmail(PendingNewsletter $newsletter): TemplatedEmail
    {
        $recipient = new Address($newsletter->email);

        $unsubscribeLink = self::WEB_URL . $this->urlGenerator->generate('unsubscribe_newsletter', [
            'email' => $newsletter->email,
            'hash' => md5('unsubscribe' . $newsletter->email . 'email'),
        ]);

        $email = (new TemplatedEmail())
            ->from('info@ceskakruta.cz')
            ->to($recipient)
            ->subject($newsletter->name)
            ->htmlTemplate('emails/newsletter.html.twig')
            ->textTemplate('emails/newsletter.txt.twig')
            ->context([
                'name' => $newsletter->name,
                'text' => $newsletter->text,
                'unsubscribe_link' => $unsubscribeLink,
                'web_url' => self::WEB_URL,
            ]);

        $email->getHeaders()->addTextHeader('X-Transport', 'newsletter');

        return $email;
    }
}
