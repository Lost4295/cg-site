<?php

namespace App\EventSubscriber;
use App\Entity\Date;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class EventCreateSubscriber implements EventSubscriberInterface
{

    private EntityManagerInterface $em;

    public function __construct( EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['createEventDate'],
            BeforeEntityUpdatedEvent::class => ['updateEventDate'],
        ];
    }

    public function createEventDate(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Event)) {
            return;
        }
        $date = new Date();
        $date->setTitle($entity->getName());
        $date->setDescription($entity->getDescription());
        $date->setDate($entity->getDate());
        $date->setDistanciel(false);
        $this->em->persist($date);
        $this->em->flush();
    }

    public function updateEventDate(BeforeEntityUpdatedEvent $event){
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Event)) {
            return;
        }
        $date = $this->em->getRepository(Date::class)->findOneBy(['title' => $entity->getName()]);
        if ($date) {
            $date->setTitle($entity->getName());
            $date->setDescription($entity->getDescription());
            $date->setDate($entity->getDate());
            $this->em->persist($date);
            $this->em->flush();
        }
    }

    public function deleteEventDate(BeforeEntityUpdatedEvent $event){
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Event)) {
            return;
        }
        $date = $this->em->getRepository(Date::class)->findOneBy(['title' => $entity->getName()]);
        if ($date) {
            $this->em->remove($date);
            $this->em->flush();
        }
    }

}
