<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Grid;

use CeskaKruta\Web\Exceptions\GridActionNotAllowed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Grid\ActivateRecord;
use CeskaKruta\Web\Message\Admin\Grid\DeactivateRecord;
use CeskaKruta\Web\Message\Admin\Grid\MoveRecord;
use CeskaKruta\Web\Message\Admin\Grid\SoftDeleteRecord;
use CeskaKruta\Web\Services\Admin\GridRedirectTarget;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use CeskaKruta\Web\Value\Admin\Grid\GridAction;
use CeskaKruta\Web\Value\Admin\Grid\MoveDirection;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Generic row actions of all admin lists (docs/admin/PLAN.md §3.3, §4.6).
 */
final class GridRecordActionController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(
        path: '/admin/record/{table}/{id}/{action}',
        name: 'admin_grid_record_action',
        requirements: ['id' => '\d+', 'action' => 'activate|deactivate|delete|move-up|move-down'],
        methods: ['POST'],
    )]
    public function __invoke(#[CurrentUser] User $admin, Request $request, string $table, int $id, string $action): Response
    {
        $adminTable = AdminTable::tryFrom($table) ?? throw $this->createNotFoundException();
        $gridAction = GridAction::from($action);

        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $message = match ($gridAction) {
            GridAction::Activate => new ActivateRecord($adminTable, $id, $admin->id),
            GridAction::Deactivate => new DeactivateRecord($adminTable, $id, $admin->id),
            GridAction::Delete => new SoftDeleteRecord($adminTable, $id, $admin->id),
            GridAction::MoveUp => new MoveRecord($adminTable, $id, MoveDirection::Up, $admin->id),
            GridAction::MoveDown => new MoveRecord($adminTable, $id, MoveDirection::Down, $admin->id),
        };

        try {
            $this->bus->dispatch($message);
        } catch (HandlerFailedException $handlerFailedException) {
            $previous = $handlerFailedException->getPrevious();

            if ($previous instanceof RecordNotFound) {
                throw $this->createNotFoundException();
            }

            if ($previous instanceof GridActionNotAllowed) {
                throw $this->createAccessDeniedException();
            }

            throw $handlerFailedException;
        }

        $this->addFlash('success', $gridAction->successMessage());

        return $this->redirect(GridRedirectTarget::fromRequest($request) . '#' . $adminTable->rowAnchorPrefix() . $id);
    }
}
