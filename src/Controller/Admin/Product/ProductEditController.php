<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Product;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\ProductFormData;
use CeskaKruta\Web\FormData\Admin\TurkeyProductFormData;
use CeskaKruta\Web\FormType\Admin\ProductFormType;
use CeskaKruta\Web\FormType\Admin\TurkeyProductFormType;
use CeskaKruta\Web\Message\Admin\Product\EditProduct;
use CeskaKruta\Web\Message\Admin\Product\EditTurkeyProduct;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProductEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminProducts $getAdminProducts,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/produkty/{id}', name: 'admin_product_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $product = $this->getAdminProducts->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_products');
        }

        if ($product->isTurkey()) {
            $turkeyFormData = TurkeyProductFormData::fromProduct($product);
            $form = $this->createForm(TurkeyProductFormType::class, $turkeyFormData);
            $message = static fn (): EditTurkeyProduct => EditTurkeyProduct::fromFormData($id, $admin->id, $turkeyFormData);
        } else {
            $formData = ProductFormData::fromProduct($product);
            $form = $this->createForm(ProductFormType::class, $formData);
            $message = static fn (): EditProduct => EditProduct::fromFormData($id, $admin->id, $formData);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch($message());

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_product_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_products') . '#item-' . $id);
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }
}
