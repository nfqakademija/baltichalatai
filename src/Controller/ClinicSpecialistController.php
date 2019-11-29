<?php

namespace App\Controller;

use App\Services\ClinicSpecialistService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class ClinicSpecialistController extends AbstractController
{
    /**
     * @var ClinicSpecialistService
     */
    private $clinicSpecialistService;

    public function __construct(ClinicSpecialistService $clinicSpecialistService)
    {
        $this->clinicSpecialistService = $clinicSpecialistService;
    }

    /**
     * @Route("/clinicspecialist/{id}/add", name="clinic_specialist_add")
     * @param UrlGeneratorInterface $urlGenerator
     * @param User $specialist
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function add(UrlGeneratorInterface $urlGenerator, User $specialist, UserInterface $user = null)
    {
        if ($user->getRole() == 3) {
            $this->clinicSpecialistService->addSpecialist($user, $specialist);
        }
        return new RedirectResponse($urlGenerator->generate('specialist_show', ['id' => $specialist->getId()]));
    }
}