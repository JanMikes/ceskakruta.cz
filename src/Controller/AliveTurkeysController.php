<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetAliveCalendar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AliveTurkeysController extends AbstractController
{
    public function __construct(
        readonly private GetAliveCalendar $getAliveCalendar,
    ) {
    }

    #[Route(path: '/krutata-k-chovu', name: 'alive_turkeys')]
    public function __invoke(): Response
    {
        $now = new \DateTimeImmutable();

        return $this->render('alive_turkeys.html.twig', [
            'calendars' => $this->getAliveCalendar->all(),
            'current_year' => $now->format('Y'),
            'current_week' => $now->format('W'),
        ]);
    }
}
