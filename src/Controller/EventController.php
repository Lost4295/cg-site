<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\AmIRegisteredType;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route(name: 'event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        dump($events);
        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }


    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = null;
        if ($this->getUser()) {
            $user = $em->getRepository(User::class)->find($this->getUser()->getId());
            $form = $this->createForm(AmIRegisteredType::class, $event, [
                'action' => $this->generateUrl('event_toogle', ['id' => $event->getId()]),
                'method' => 'GET',
            ]);
            if ($event->getParticipants()->contains($this->getUser())) {
                $form->get('going')->setData(true);
            }
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $going = $form->get('going')->getData();
                $user = $em->getRepository(User::class)->find($this->getUser()->getId());
                if ($going) {
                    $event->addParticipant($user);
                } else {
                    $event->removeParticipant($user);
                }
                $em->persist($event);
                $em->flush();
            }
        }
        return $this->render('event/show.html.twig', [
            'event' => $event,
            'form' => $form === null ? $form: $form->createView(),
        ]);
    }

    #[Route('/{id}/tog', name: 'event_toogle', methods: ['GET'])]
    #[IsGranted("ROLE_USER")]
    public function toggle(Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($this->getUser()->getId());
        if ($event->getParticipants()->contains($user)) {
            $event->removeParticipant($user);
        } else {
            $event->addParticipant($user);
        }
        $entityManager->persist($event);
        $entityManager->flush();


        return $this->redirectToRoute('event_index', [], Response::HTTP_SEE_OTHER);
    }
}
