<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserVisit;
use App\Services\UserVisitService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVisitController extends AbstractController
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var UserVisitService
     */
    private $visitService;
    /**
     * @var FlashBagInterface
     */
    private $bag;

    /**
     * UserVisitController constructor.
     * @param UrlGeneratorInterface $urlGenerator
     * @param UserVisitService $visitService
     * @param FlashBagInterface $bag
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UserVisitService $visitService,
        FlashBagInterface $bag
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->visitService = $visitService;
        $this->bag = $bag;
    }

    /**
     * @Route("/uservisit/{id}/delete", name="delete_user_visit")
     * @param UserVisit $userVisit
     * @param UserInterface $user
     * @return RedirectResponse
     */
    public function deleteUserVisit(
        UserVisit $userVisit,
        UserInterface $user
    ) {
        if ($user instanceof User && $user->getId() == $userVisit->getClientId()->getId()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($userVisit);
            $em->flush();
            $this->bag->add('success', 'Vizitas buvo atšauktas.');
            return new RedirectResponse($this->urlGenerator->generate('patient'));
        } elseif ($user instanceof User && $user->getId() == $userVisit->getSpecialistId()->getId()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($userVisit);
            $em->flush();
            $this->bag->add('success', 'Vizitas buvo atšauktas.');
            return new RedirectResponse($this->urlGenerator->generate('specialist'));
        }
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    /**
     * @Route ("/uservisit/{id}/close", name="close_visit")
     * @param UserVisit $userVisit
     * @param UserInterface $user
     * @return RedirectResponse
     */
    public function closeVisit(UserVisit $userVisit, UserInterface $user = null)
    {
        if ($user instanceof User && $user->getId() == $userVisit->getSpecialistId()->getId()) {
            if ($userVisit->getDescription() != "") {
                $em = $this->getDoctrine()->getManager();
                $userVisit->setIsCompleted(true);
                $em->flush();

                $this->bag->add('success', 'Vizitas uždarytas');

                return new RedirectResponse($this->urlGenerator->generate('specialist'));
            } else {
                $this->bag->add('error', 'Prašome užpildyti vizito informaciją.');

                return new RedirectResponse($this->urlGenerator->generate('visit_info', ['id' => $userVisit->getId()]));
            }
        }

        // comment to force git to update migration
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    /**
     * @Route ("/uservisit/{id}/visit_info", name="visit_info")
     * @param UserVisit $userVisit
     * @param UserInterface $user
     * @return Response
     */
    public function showVisitCommentary(UserVisit $userVisit, UserInterface $user = null)
    {
        if ($user instanceof User && $user->getId() == $userVisit->getSpecialistId()->getId()) {
            return $this->render('user_visit/index.html.twig', [
                'userVisit' => $userVisit,
            ]);
        } else {
            throw $this->createNotFoundException('Puslapis nerastas');
        }
    }

    /**
     * @Route ("/uservisit/{id}/save_visit_info", name="save_visit_info")
     * @param UserVisit $visit
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveVisitInfo(UserVisit $visit, Request $request)
    {
        $this->visitService->saveVisitInfo($visit, $request->get('visit-comment'));

        $this->bag->add('success', 'Išsaugota vizito informacija');

        return $this->redirectToRoute('visit_info', ['id' => $visit->getId()]);
    }

    /**
     * @Route ("/uservisit/{id}/save_recipe_info", name="save_recipe_info")
     * @param UserVisit $visit
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function saveRecipeInfo(UserVisit $visit, Request $request)
    {
        $this->visitService->saveRecipeInfo($visit, $request->get('recipe-comment'), $request->get('recipe-duration'));

        $this->bag->add('success', 'Išsaugota recepto informacija');

        return $this->redirectToRoute('visit_info', ['id' => $visit->getId()]);
    }

    /**
     * @Route ("/uservisit/{id}/save_sending_info", name="save_sending_info")
     * @param UserVisit $visit
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveSendingToDoctorInfo(UserVisit $visit, Request $request)
    {
        $this->visitService->saveSendingToDoctorInfo($visit, $request->get('sending-to-doctor-comment'));

        $this->bag->add('success', 'Išsaugota siuntimo informacija');

        return $this->redirectToRoute('visit_info', ['id' => $visit->getId()]);
    }
}
