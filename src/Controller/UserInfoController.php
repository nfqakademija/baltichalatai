<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Entity\User;
use App\Entity\UserInfo;
use App\Form\UserInfoType;
use App\Services\UserAuthService;
use App\Services\UserInfoService;
use App\Services\UserSpecialtyService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserInfoController extends AbstractController
{

    /**
     * @var UserInfoService
     */
    private $userInfoService;
    /**
     * @var FlashBagInterface
     */
    private $bag;
    /**
     * @var UserSpecialtyService
     */
    private $userSpecialtyService;
    /**
     * @var UserAuthService
     */
    private $userAuthService;

    /**
     * UserInfoController constructor.
     * @param UserInfoService $userInfoService
     * @param FlashBagInterface $bag
     * @param UserSpecialtyService $userSpecialtyService
     * @param UserAuthService $userAuthService
     */
    public function __construct(
        UserInfoService $userInfoService,
        FlashBagInterface $bag,
        UserSpecialtyService $userSpecialtyService,
        UserAuthService $userAuthService
    ) {
        $this->userInfoService = $userInfoService;
        $this->userSpecialtyService = $userSpecialtyService;
        $this->userAuthService = $userAuthService;
        $this->bag = $bag;
    }

    /**
     * @Route("/userinfo/edit", name="userinfo_edit")
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param UserInterface|null $user
     * @return Response
     */
    public function edit(Request $request, UrlGeneratorInterface $urlGenerator, UserInterface $user = null)
    {
        if ($this->userAuthService->isSpecialist($user) ||
            $this->userAuthService->isPatient($user)) {
            $userInfo = $user->getUserInfo()->first();
            if ($userInfo == false) {
                $userInfo = new UserInfo();
            }
            $form = $this->createForm(UserInfoType::class, $userInfo, [
                'action' => $this->generateUrl('userinfo_edit'),
            ]);
            $specialtiesForm = $this->createSpecialistForm();

            //Handle the request
            $form->handleRequest($request);
            $specialtiesForm->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid() &&
                $this->userInfoService->validateUserInfoForm($request->request->get('user_info')) == "") {
                $userInfo->setUserId($user);
                $userInfo->setPersonCode('');
                $em = $this->getDoctrine()->getManager();
                $em->persist($userInfo);
                $em->flush();

                $this->bag->add('success', 'Jūsų informacija buvo išsaugota.');
            } elseif ($specialtiesForm->isSubmitted() && $specialtiesForm->isValid()) {
                $responseData = $request->request->get('form');
                $this->userSpecialtyService->addSpecialty(
                    $responseData['specialties'],
                    $user
                );
            }
            return $this->render('user_info/edit.html.twig', [
                'user_info_form' => $form->createView(),
                'specialtiesForm' => $specialtiesForm->createView(),
            ]);
        }
        return new RedirectResponse($urlGenerator->generate('app_login'));
    }

    private function createSpecialistForm(UserInterface $user = null)
    {
        $specialties = $this->getDoctrine()->getRepository(Specialty::class)->findAll();
        $choices = array();
        foreach ($specialties as $specialty) {
            $choices += array($specialty->getName() => $specialty->getId());
        }

        $specialtiesForm = $this->createFormBuilder([])
            ->add('specialties', ChoiceType::class, [
                'placeholder' => 'Specialybė',
                'choices' => $choices,
                'required' => false,
            ])
//            ->add('custom_specialty', TextType::class, ['required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Prideti'])
            ->getForm();

        return $specialtiesForm;
    }
}
