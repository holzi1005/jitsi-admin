<?php

namespace App\Controller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Form\Type\NewMemberType;
use App\Form\Type\RepeaterType;
use App\Form\Type\RoomType;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepeaterController extends AbstractController
{
    /**
     * @Route("/room/repeater/new", name="repeater_new")
     */
    public function index(Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {
        //todo check if allowed
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));

        $repeater = new Repeat();
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_new', ['room' => $room->getId()])]);

        try {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repeater = $form->getData();
            $em = $this->getDoctrine()->getManager();
            foreach ($room->getUser() as $data) {
                $room->addPrototypeUser($data);
            }
            $em->persist($room);
            $em->flush();
            $repeater->setPrototyp($room);
            $repeater->setStartDate($room->getStart());
            $em->persist($repeater);
            $em->flush();
            $repeaterService->createNewRepeater($repeater);
            foreach ($room->getUser() as $data) {
                $room->removeUser($data);
            }
            $em->persist($room);
            $em->flush();
            $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin erstellt');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
        }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/room/repeater/edit/repeat", name="repeater_edit_repeater")
     */
    public function editRepeater(Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {
        //todo check if allowed
        $repeater = $this->getDoctrine()->getRepository(Repeat::class)->find($request->get('repeat'));
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_edit_repeater', ['repeat' => $repeater->getId()])]);

        try {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repeater = $form->getData();
            $em = $this->getDoctrine()->getManager();

            foreach ($repeater->getRooms() as $data) {
                $em->remove($data);
            }
            $em->persist($repeater);
            $em->flush();

            $repeaterService->createNewRepeater($repeater);
            $room = $repeater->getPrototyp();
            foreach ($room->getUser() as $data) {
                $room->removeUser($data);
            }
            $em->persist($room);
            $em->flush();
            $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin bearbeitet');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
        }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/room/repeater/remove", name="repeater_remove")
     */
    public function removeRepeater(Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {
        //todo check if allowed
        $repeater = $this->getDoctrine()->getRepository(Repeat::class)->find($request->get('repeat'));
        $em = $this->getDoctrine()->getManager();

        foreach ($repeater->getRooms() as $data) {
            $em->remove($data);
        }
        $prototype = $repeater->getPrototyp();
        $repeater->setPrototyp(null);
        $em->persist($repeater);
        $em->flush();
        $em->remove($repeater);
        $em->flush();
        $em->remove($prototype);
        $em->flush();
        $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin gelöscht');
        return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
    }

    /**
     * @Route("/room/repeater/edit/room", name="repeater_edit_room")
     */
    public function editPrototype(Request $request, UserService $userService,TranslatorInterface $translator, RepeaterService $repeaterService, ServerUserManagment $serverUserManagment): Response
    {
        //todo check if allowed
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('id'));
        $repeater = $room->getRepeater();
        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('repeater_edit_room', ['id' => $room->getId()])]);
        $form->add('repeaterRemoved', CheckboxType::class, array(
            'label' => 'label.repeaterRemoved',
            'required' => false,
            'translation_domain' => 'form'));
        try {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $room = $form->getData();
            if($room->getRepeaterRemoved()){
                foreach ($room->getUser() as $data){
                    $userService->editRoom($data,$room);
                }
                $em->persist($room);
                $em->flush();
                $snack = $translator->trans('Sie haben Erfolgreich einen Termin aus einer Terminserie bearbeitet');
                return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
            }

            foreach ($room->getUser() as $data) {
                $room->addPrototypeUser($data);

            }
            $em->persist($room);
            $em->flush();

            $proOld = $repeater->getPrototyp();
            $proOld->setRepeater(null);
            $em->persist($proOld);
            $proDub = clone $room;
            $em->persist($proDub);
            $room->setRepeater(null);
            $repeater->setPrototyp($room);
            $em->persist($repeater);
            $em->flush();

            $repeaterService->replaceRooms($repeater, $room);
            foreach ($room->getUser() as $data) {
                $room->removeUser($data);
            }
            $em->persist($room);
            $em->flush();
            $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin bearbeitet');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
        }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('base/__newRoomModal.html.twig', [
            'form' => $form->createView(),
            'title' => $translator->trans('Serienelement bearbeiten')
        ]);
    }

}
