<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Twig;

use CeskaKruta\Web\FormType\SubscribeNewsletterFormType;
use DateTimeImmutable;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    public function __construct(
        readonly private KernelInterface $kernel,
        readonly private FormFactoryInterface $formFactory,
    ) {
    }

    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('dayOfWeek', $this->dayOfWeek(...)),
            new TwigFilter('price', $this->formatPrice(...)),
        ];
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exists', $this->asset_exists(...)),
            new TwigFunction('firstDayOfWeek', [$this, 'getFirstDayOfWeek']),
            new TwigFunction('lastDayOfWeek', [$this, 'getLastDayOfWeek']),
            new TwigFunction('newsletterForm', [$this, 'getNewsletterForm']),
        ];
    }

    public function getFirstDayOfWeek(int $year, int $week): DateTimeImmutable
    {
        $startOfWeek = new \DateTimeImmutable();
        $startOfWeek = $startOfWeek->setISODate($year, $week, 1); // 1 indicates Monday

        return $startOfWeek;
    }

    public function getLastDayOfWeek(int $year, int $week): DateTimeImmutable
    {
        $startOfWeek = new \DateTimeImmutable();
        $startOfWeek = $startOfWeek->setISODate($year, $week, 1); // 1 indicates Monday
        $endOfWeek = $startOfWeek->modify('sunday this week');

        return $endOfWeek;
    }

    public function asset_exists(string $path): bool
    {
        $webRoot = realpath($this->kernel->getProjectDir() . '/public/');
        assert(is_string($webRoot));

        $toCheck = realpath(rtrim($webRoot, '/') . '/' . $path);

        // check if the file exists
        if ($toCheck === false || !is_file($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($webRoot, $toCheck, strlen($webRoot)) !== 0) {
            return false;
        }

        return true;
    }


    public function dayOfWeek(DateTimeImmutable $date): string
    {
        $dayOfWeek = (int) $date->format('w');

        return match($dayOfWeek) {
            0 => 'Neděle',
            1 => 'Pondělí',
            2 => 'Úterý',
            3 => 'Středa',
            4 => 'Čtvrtek',
            5 => 'Pátek',
            6 => 'Sobota',
        };
    }

    public function formatPrice(float|int $price): string
    {
        return number_format($price, thousands_separator: ' ');
    }

    public function getNewsletterForm(): FormView
    {
        return $this->formFactory->create(SubscribeNewsletterFormType::class)->createView();
    }
}
