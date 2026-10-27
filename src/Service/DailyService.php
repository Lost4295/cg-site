<?php

namespace App\Service;

use App\Entity\CouchPoint;
use App\Entity\Daily;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class DailyService
{
    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    public function giveDailyPoints(User $user): int
    {
       return $this->em->wrapInTransaction(function () use ($user): int {
            // Verrouille l'utilisateur jusqu'à la fin de la transaction.
            $this->em->lock($user, LockMode::PESSIMISTIC_WRITE);
            $today = new DateTime();
            $dailies = $this->em->getRepository(Daily::class)->findBy(['user_id' => $user->getId(), 'location' => "site"], ['date' => 'DESC'], 1);

            if (!empty($dailies)) {
                $dailyDate = new DateTime($dailies[0]->getDate());
                if ($dailyDate->format('Y-m-d') === $today->format('Y-m-d')) {
                    return 0;
                }
            }

            $value = random_int(1, 50);
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
        });
    }
}
