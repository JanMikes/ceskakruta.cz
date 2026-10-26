<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Grid;

use CeskaKruta\Web\Exceptions\GridActionNotAllowed;
use CeskaKruta\Web\Message\Admin\Grid\ActivateAllRecords;
use CeskaKruta\Web\Services\Admin\GridRedirectTarget;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GridActivateAllController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/record/{table}/activate-all', name: 'admin_grid_activate_all', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, string $table): Response
    {
        $adminTable = AdminTable::tryFrom($table) ?? throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $scope = $request->request->get('scope');
        $scope = is_numeric($scope) ? (int) $scope : null;

        try {
            $this->bus->dispatch(new ActivateAllRecords($adminTable, $scope, $admin->id));
        } catch (HandlerFailedException $handlerFailedException) {
            if ($handlerFailedException->getPrevious() instanceof GridActionNotAllowed) {
                throw $this->createAccessDeniedException();
            }

            throw $handlerFailedException;
        }

        $this->addFlash('success', 'Záznamy úspěšně aktivovány');

        return $this->redirect(GridRedirectTarget::fromRequest($request));
    }
}
