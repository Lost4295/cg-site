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

        $points = $em->getRepository(Point::class)->getPointsOfUsers();
        return $this->render('point/index.html.twig', [
            'points' => $points,
        ]);
    }
}
