<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AdminLoginController extends AbstractController
{
    private const INVALID_CREDENTIALS_MESSAGE = 'Byly zadány neplatné přihlašovací údaje!';

    public function __construct(
        readonly private AuthenticationUtils $authenticationUtils,
    ) {
    }

    #[Route(path: '/admin/login', name: 'admin_login')]
    public function __invoke(#[CurrentUser] null|User $user): Response
    {
        if ($user !== null && $this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $errorMessage = null;

        if ($error instanceof CustomUserMessageAccountStatusException) {
            $errorMessage = $error->getMessageKey();
        } elseif ($error !== null) {
            $errorMessage = self::INVALID_CREDENTIALS_MESSAGE;
        }

        return $this->render('admin/login.html.twig', [
            'last_username' => $this->authenticationUtils->getLastUsername(),
            'error_message' => $errorMessage,
        ]);
    }
}
