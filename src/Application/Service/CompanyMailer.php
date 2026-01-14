<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Message\SendMailMessage;
use App\Domain\Entity\Company;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

class CompanyMailer
{
    public function __construct(
        private Environment $twig,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {}

    public function sendCreated(Company $company): void
    {
        $subject = $this->translator->trans('email.company.created.subject');
        $bodyContent = $this->translator->trans(
            'email.company.created.body',
            [
                '%longName%' => $company->getLongName(),
                '%email%' => $company->getEmail(),
                '%city%' => $company->getCity(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($company->getEmail(), $subject, $body)
        );
    }

    public function sendUpdated(Company $company): void
    {
        $subject = $this->translator->trans('email.company.updated.subject');
        $bodyContent = $this->translator->trans(
            'email.company.updated.body',
            [
                '%longName%' => $company->getLongName(),
                '%email%' => $company->getEmail(),
                '%city%' => $company->getCity(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($company->getEmail(), $subject, $body)
        );
    }

    public function sendChangeActive(Company $company): void
    {
        $subject = $this->translator->trans('email.company.changeActive.subject');
        $bodyContent = $this->translator->trans(
            'email.company.changeActive.body',
            [
                '%longName%' => $company->getLongName(),
                '%email%' => $company->getEmail(),
                '%city%' => $company->getCity(),
                '%isActive%' => $company->isActive() ? 'true' : 'false',
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($company->getEmail(), $subject, $body)
        );
    }

    public function sendDeleted(Company $company): void
    {
        $subject = $this->translator->trans('email.company.deleted.subject');
        $bodyContent = $this->translator->trans(
            'email.company.deleted.body',
            [
                '%longName%' => $company->getLongName(),
                '%email%' => $company->getEmail(),
                '%city%' => $company->getCity(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($company->getEmail(), $subject, $body)
        );
    }

    private function renderBaseTemplate(string $title, string $content): string
    {
        return $this->twig->render(
            'emails/base.html.twig',
            [
                'title' => $title,
                'content' => $content
            ]
        );
    }
}
