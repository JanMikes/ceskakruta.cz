<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Vendor;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\VendorFormData;
use CeskaKruta\Web\FormType\Admin\VendorFormType;
use CeskaKruta\Web\Message\Admin\Vendor\EditVendor;
use CeskaKruta\Web\Query\Admin\GetAdminVendors;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class VendorEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminVendors $getAdminVendors,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/prodejci/{id}', name: 'admin_vendor_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $vendor = $this->getAdminVendors->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_vendors');
        }

        $formData = VendorFormData::fromVendor($vendor);
        $form = $this->createForm(VendorFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditVendor::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_vendor_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_vendors') . '#item-' . $id);
        }

        return $this->render('admin/vendor/form.html.twig', [
            'form' => $form,
            'vendor' => $vendor,
        ]);
    }
}
