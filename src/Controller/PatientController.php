<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserVisit;
use App\Services\PatientServices;
use App\Services\UserAuthService;
use App\Services\UserVisitService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PatientController extends AbstractController
{
    /**
     * @var PatientServices
     */
    private $patientServices;
    /**
     * @var FlashBagInterface
     */
    private $bag;
    /**
     * @var UserAuthService
     */
    private $userAuthService;

    /**
     * PatientController constructor.
     * @param PatientServices $patientServices
     * @param UserAuthService $userAuthService
     * @param FlashBagInterface $bag
     */
    public function __construct(
        PatientServices $patientServices,
        UserAuthService $userAuthService,
        FlashBagInterface $bag
    ) {
        $this->patientServices = $patientServices;
        $this->userAuthService = $userAuthService;
        $this->bag = $bag;
    }


    /**
     * @Route("/patient/show/{id}", name="patient_show")
     * @param User $patient
     * @return Response
     */
    public function show(User $patient)
    {
        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    /**
     * @Route("/patient", name="patient")
     * @param UrlGeneratorInterface $urlGenerator
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param UserInterface|null $user
     * @return Response
     */
    public function panel(
        UrlGeneratorInterface $urlGenerator,
        PaginatorInterface $paginator,
        Request $request,
        UserInterface $user = null
    ) {
        if (!$this->userAuthService->isPatient($user)) {
            return new RedirectResponse($urlGenerator->generate('app_login'));
        }
        if (is_bool($user->getUserInfo()->first())) {
            $this->bag->add('error', 'Prašome užpildyti asmeninę informaciją');
            return new RedirectResponse($urlGenerator->generate('userinfo_edit'));
        }
        $queryBuilder = $this->getDoctrine()->getRepository(UserVisit::class)
            ->getWithPatientIdCompletedQueryBuilder($user->getId());

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );

        $upcomingVisits = $this->getDoctrine()->getRepository(UserVisit::class)
            ->findByPatientIdNotCompleted($user->getId());

        return $this->render('patient/home.html.twig', [
            'upcomingVisits' => $upcomingVisits,
            'pastVisits' => $pagination,
            'userInfo' => $user->getUserInfo()->first(),
            'clientRecipes' => $this->patientServices->getClientRecipes($user),
            'clientSendings' => $this->patientServices->getClientSendingsToDoctor($user),
        ]);
    }
}
