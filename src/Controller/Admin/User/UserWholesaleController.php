<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\User;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\User\SetUserWholesale;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UserWholesaleController extends AbstractController
{
    public function __construct(
        readonly private GetAdminUsers $getAdminUsers,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/uzivatele/{id}/velkoobchod', name: 'admin_user_wholesale', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $user = $this->getAdminUsers->oneById($id);
        } catch (RecordNotFound) {
            throw $this->createNotFoundException();
        }

        $allow = $request->request->getString('allow') === '1';
        $this->bus->dispatch(new SetUserWholesale($id, $allow, $admin->id));

        $this->addFlash('success', sprintf(
            'Velkoprodej byl %s pro uživatele: %s',
            $allow ? 'povolen' : 'zakázán',
            $user->email,
        ));

        return $this->redirect($this->generateUrl('admin_users') . '#item-' . $id);
    }
}
