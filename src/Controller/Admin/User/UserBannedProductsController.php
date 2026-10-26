<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\User;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\UserBannedProductsFormData;
use CeskaKruta\Web\FormType\Admin\UserBannedProductsFormType;
use CeskaKruta\Web\Message\Admin\User\SetUserBannedProducts;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Query\Admin\GetUserBannedProducts;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UserBannedProductsController extends AbstractController
{
    public function __construct(
        readonly private GetAdminUsers $getAdminUsers,
        readonly private GetUserBannedProducts $getUserBannedProducts,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/uzivatele/{id}/zakazane-produkty', name: 'admin_user_banned_products', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $user = $this->getAdminUsers->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_users');
        }

        $formData = new UserBannedProductsFormData();
        $formData->products = $this->getUserBannedProducts->bannedProductIds($id);

        $form = $this->createForm(UserBannedProductsFormType::class, $formData, [
            'products' => $this->getUserBannedProducts->productChoices(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(new SetUserBannedProducts($id, $formData->products, $admin->id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_users') . '#item-' . $id);
        }

        return $this->render('admin/user/banned_products.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
