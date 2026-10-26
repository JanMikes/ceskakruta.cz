<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Vendor;

use CeskaKruta\Web\FormData\Admin\VendorFormData;
use CeskaKruta\Web\FormType\Admin\VendorFormType;
use CeskaKruta\Web\Message\Admin\Vendor\CreateVendor;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class VendorCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/prodejci/pridat', name: 'admin_vendor_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new VendorFormData();
        $form = $this->createForm(VendorFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateVendor::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_vendors') . '#item-' . $id);
        }

        return $this->render('admin/vendor/form.html.twig', [
            'form' => $form,
            'vendor' => null,
        ]);
    }
}
