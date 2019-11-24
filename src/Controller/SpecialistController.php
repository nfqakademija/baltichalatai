<?php

namespace App\Controller;

use App\Entity\SpecialistWorkHours;
use App\Services\SpecialistService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Specialty;
use App\Entity\User;
use App\Entity\UserSpecialty;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SpecialistController extends AbstractController
{
    private $specialistService;

    public function __construct(SpecialistService $specialistService)
    {
        $this->specialistService = $specialistService;
    }

    /**
     * @Route("/specialist", name="specialist")
     */
    public function index()
    {
        return $this->render('specialist/index.html.twig', [
            'controller_name' => 'SpecialistController',
        ]);
    }

    /**
     * @Route("/specialist/show/{id}", name="specialist_show")
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        $specialist = $this->specialistService->getSpecialist($id);
        if (sizeof($specialist) == 0) {
            throw $this->createNotFoundException();
        } else {
            $workHours = $this->specialistService->getSpecialistWorkHours($specialist[0]);
        }

        return $this->render('specialist/index.html.twig', [
            'specialist' => $specialist[0],
            'workHours' => $this->specialistService->getSpecialistHoursFormatted($workHours),
        ]);
    }

    /**
     * @Route("/specialist/{id}/hours_edit", name="specialist_hours_edit")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function editHours($id, Request $request)
    {
        if (!is_null($request->get('day'))) {
            $manager = $this->getDoctrine()->getManager();

            $specialist = $this->specialistService->getSpecialist($id);
            $clinic = $this->specialistService->getClinic($request->get('clinicId'));

            $workHours = $this->specialistService->getWorkHours($id, $clinic[0]->getId());
            foreach ($workHours as $workHour) { //ismetam visus laikus
                $manager->remove($workHour);
            }
            foreach ($request->get('day') as $key => $day) {
                //praskipinam jeigu nieko neideta, kad nesugeneruotu default laiku
                if ($day['startTime'] == "" || $day['endTime'] == "") {
                    continue;
                }
                $workHours = new SpecialistWorkHours(); //sudedam naujus laikus
                $workHours->setClinicId($clinic[0]);
                $workHours->setSpecialistId($specialist[0]);
                $workHours->setDay($key);
                $workHours->setStartTime(new DateTime($day['startTime']));
                $workHours->setEndTime(new DateTime($day['endTime']));

                $manager->persist($workHours);
            }
            $manager->flush();
        }

        $specialist = $this->specialistService->getSpecialist($id);
        if (sizeof($specialist) == 0) {
            throw $this->createNotFoundException();
        }

        $workHours = $this->specialistService->getSpecialistWorkHours($specialist[0]);
        $specClinics = $this->specialistService->getSpecialistClinics($id);

        return $this->render('specialist/hours_edit.html.twig', [
            'workDayList' => $this->specialistService->getWorkdayList(),
            'specClinics' => $specClinics,
            'workHours' => $workHours,
        ]);
    }

    /**
     * @Route("/specialist/edit", name="specialist_edit")
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param UserInterface|null $user
     * @return Response
     */
    public function edit(Request $request, UrlGeneratorInterface $urlGenerator, UserInterface $user = null)
    {
        if ($user instanceof User && $user->getRole() == 2) {
            $specialtiesForm = $this->createSpecialistForm();

            $specialtiesForm->handleRequest($request);

            if ($specialtiesForm->isSubmitted() && $specialtiesForm->isValid()) {
                // Add specialty from dropdown
                if ($request->request->get('form')['specialties'] != "") {
                    //Check if user does not have that specialty
                    $specialty = $this->getDoctrine()->getRepository(UserSpecialty::class)
                        ->findByUserIdAndSpecialtyId($user->getId(), $request->request->get('form')['specialties']);

                    if (sizeof($specialty) == 0) {
                        $userSpecialty = new UserSpecialty();
                        $userSpecialty->setUserId($user);
                        $specialty = $this->getDoctrine()->getRepository(Specialty::class)
                            ->findOneById($request->request->get('form')['specialties']);
                        $userSpecialty->setSpecialtyId($specialty);

                        $em = $this->getDoctrine()->getManager();

                        $em->persist($userSpecialty);
                        $em->flush();
                    } else {
                        // Flash message that you already have that specialty added.
                        echo 'jus jau pasirinkes tokia specialybe';
                    }
                } elseif ($request->request->get('form')['custom_specialty'] != "") {// Add specialty from text box.
                    // Check if specialty already exists.
                    $specialty = $this->getDoctrine()->getRepository(Specialty::class)
                        ->findBySpecialtyName($request->request->get('form')['custom_specialty']);

                    if (sizeof($specialty) == 0) {
                        $userSpecialty = new UserSpecialty();
                        $specialty = new Specialty();
                        $specialty->setName($request->request->get('form')['custom_specialty']);
                        $em = $this->getDoctrine()->getManager();

                        $em->persist($specialty);
                        $em->flush();

                        $userSpecialty->setUserId($user);
                        $userSpecialty->setSpecialtyId($specialty);
                        $em->persist($userSpecialty);
                        $em->flush();
                    } else {
                        // Flash message that specialty already exists
                        echo 'specialty already exists';
                    }
                }
            }

            return $this->render('specialist/edit.html.twig', [
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
                'choices' => $choices,
                'required' => false,
            ])
            ->add('custom_specialty', TextType::class, ['required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Prideti'])
            ->getForm();

        return $specialtiesForm;
    }
}
