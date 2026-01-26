<?php

namespace App\Controller;

use App\Entity\Point;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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


    #[Route('/points-open/NJXWQYLONZQWM2LMNFXWY', name: 'responsible_points')]
    public function responsiblePoints(): Response
    {
        return $this->render('point/opens.html.twig');
    }

    #[Route('/get/NJXWQYLONZQWM2LMNFXWY', name: 'api_points')]
    public function getOPENS(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $keyword = $request->query->get('search', '');
        $points = $em->getRepository(Point::class)->getOpenPointsData($keyword);
        foreach ($points as &$point) {
            $point["name"] = strtoupper($point["nom"]) . " " . ucwords(strtolower($point["prenom"]));
            unset($point["nom"]);
            unset($point["prenom"]);
            $point["classe"] = $point["classe"]. $point["groupe"];
            unset($point["groupe"]);
        }

        return new JsonResponse($points);
    }

    #[Route('/NJXWQYLONZQWM2LMNFXWY/{id}', name: 'points_responsible_precise', requirements: ['id' => '\d+'])]
    public function getOPENSforUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $point = $em->getRepository(Point::class)->getOpenPointsDataPrecise($id)[0];
        if (!$point) {
            return new Response("Point not found", 404);
        }
        return $this->render('point/point_detail.html.twig', [
            'point' => $point,
        ]);
    }

}
