<?php

namespace App\Controller;

use App\Entity\Point;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PointController extends AbstractController
{
    #[Route('/point', name: 'app_points')]
    public function index(EntityManagerInterface $em): Response
    {

        $points1 = $em->getRepository(Point::class)->getPointsOfUsers();
        $points2 = $em->getRepository(Point::class)->getPointsOfUsers(2);
        $points3 = $em->getRepository(Point::class)->getPointsOfUsers(3);
        return $this->render('point/index.html.twig', [
            'points1' => $points1,
            'points2' => $points2,
            'points3' => $points3,
        ]);
    }
}
