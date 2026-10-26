<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\User;

use CeskaKruta\Web\Exceptions\CannotRevokeOwnAdmin;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\UserFormData;
use CeskaKruta\Web\FormType\Admin\UserFormType;
use CeskaKruta\Web\Message\Admin\User\EditUser;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UserEditController extends AbstractController
{
    /**
     * Not a place of its own: the old form relabelled place 22 (or added it) for wholesale customers.
     */
    private const WHOLESALE_PLACE_ID = 22;
    private const WHOLESALE_PLACE_LABEL = 'Rozvoz Česká Krůta - Velkoobchod všechny dny';

    public function __construct(
        readonly private GetAdminUsers $getAdminUsers,
        readonly private GetAdminPlaces $getAdminPlaces,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/uzivatele/{id}', name: 'admin_user_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $user = $this->getAdminUsers->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_users');
        }

        $places = $this->getAdminPlaces->titlesForChoice();
        $places[self::WHOLESALE_PLACE_ID] = self::WHOLESALE_PLACE_LABEL;

        $formData = UserFormData::fromUser($user);
        $form = $this->createForm(UserFormType::class, $formData, [
            'places' => $places,
            'own_account' => $id === $admin->id,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bus->dispatch(EditUser::fromFormData($id, $admin->id, $formData));

                $this->addFlash('success', 'Záznam byl úspěšně uložen!');

                if ($request->request->has('_continue')) {
                    return $this->redirectToRoute('admin_user_edit', ['id' => $id]);
                }

                return $this->redirect($this->generateUrl('admin_users') . '#item-' . $id);
            } catch (HandlerFailedException $handlerFailedException) {
                if (!$handlerFailedException->getPrevious() instanceof CannotRevokeOwnAdmin) {
                    throw $handlerFailedException;
                }

                $this->addFlash('danger', 'Vlastní administrátorské oprávnění nelze odebrat.');

                return $this->redirectToRoute('admin_user_edit', ['id' => $id]);
            }
        }

        return $this->render('admin/user/form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
