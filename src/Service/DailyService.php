<?php

namespace App\Service;

use App\Entity\CouchPoint;
use App\Entity\Daily;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DailyService
{
    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    public function giveDailyPoints(UserInterface $user)
    {
        $today = new DateTime();
        $dailies = $this->em->getRepository(Daily::class)->findBy(['user_id' => $user->getId(), 'location' => "site"], ['date' => 'DESC']);
        foreach ($dailies as $daily) {
            $dailyDate = new DateTime($daily->getDate());
            if ($dailyDate->format('Y-m-d') === $today->format('Y-m-d')) {
                return 0;
            }
        }
        $value =rand(1, 50);
        $couchPoint = new CouchPoint();
        $couchPoint->setUser($user);
        $couchPoint->setPoints($value);
        $couchPoint->setDate($today);
        $this->em->persist($couchPoint);
        $daily = new Daily();
        $daily->setUserId($user->getId());
        $daily->setDate($today->format('Y-m-d H:i:s'));
        $daily->setLocation("site");
        $this->em->persist($daily);
        $this->em->flush();
        return $value;
    }
}
