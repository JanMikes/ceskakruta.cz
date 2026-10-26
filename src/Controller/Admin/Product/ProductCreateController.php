<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Product;

use CeskaKruta\Web\FormData\Admin\ProductFormData;
use CeskaKruta\Web\FormType\Admin\ProductFormType;
use CeskaKruta\Web\Message\Admin\Product\CreateProduct;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProductCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/produkty/pridat', name: 'admin_product_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new ProductFormData();
        $form = $this->createForm(ProductFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateProduct::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_products') . '#item-' . $id);
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form,
            'product' => null,
        ]);
    }
}
