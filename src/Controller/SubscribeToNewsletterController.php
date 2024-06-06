<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\EmailAlreadySubscribedToNewsletter;
use CeskaKruta\Web\FormData\SubscribeNewsletterFormData;
use CeskaKruta\Web\FormType\SubscribeNewsletterFormType;
use CeskaKruta\Web\Message\SubscribeNewsletter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SubscribeToNewsletterController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {}

    #[Route(path: '/odber-aktualit', name: 'subscribe_newsletter', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $data = new SubscribeNewsletterFormData();
        $form = $this->createForm(SubscribeNewsletterFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bus->dispatch(
                    new SubscribeNewsletter($data->email),
                );

                $this->addFlash('success', 'Už vám nic neunikne, přihlásili jsme vás k oběru novinek.');
            } catch (HandlerFailedException $handlerFailedException) {
                $previousException = $handlerFailedException->getPrevious();

                if ($previousException instanceof EmailAlreadySubscribedToNewsletter) {
                    $this->addFlash('warning', 'Váš e-mail byl již v minulosti přihlášen k odběru. O žádnou z našich novinek nepřijdete!');
                } else {
                    throw $handlerFailedException;
                }
            }
        }

        $referer = $request->headers->get('referer');

        if (!is_string($referer)) {
            return $this->redirectToRoute('homepage');
        }

        return $this->redirect($referer);
    }
}
