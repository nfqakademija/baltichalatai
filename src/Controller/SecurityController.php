<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\User;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile(UrlGeneratorInterface $urlGenerator, UserInterface $user = null)
    {
        if ($user instanceof User) {
            return $this->render('security/profile.html.twig', [
                'user' => $user,
                'userId' => $user->getId(),
                'roles' => $this->roleNames($user->getRoles()),
            ]);
        }
        // Redirect for not logged in users (or different kind)
        return new RedirectResponse($urlGenerator->generate('app_login'));
    }
    private function roleNames(array $userRoles)
    {
        foreach ($userRoles as $role) {
            yield str_replace('ROLE_', '', $role);
        }
    }
}
