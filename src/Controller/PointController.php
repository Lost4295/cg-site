<?php

namespace App\Controller;

use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;
use App\Service\PointCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PointController extends AbstractController
{
    #[Route('/point', name: 'app_points')]
    public function index(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        $allPoints = $em->getRepository(Point::class)->findAll();
        $allTrimestres = $em->getRepository(Trimestre::class)->findAll();
        $userPoints = [];
        $trimestre1Points = [];
        $trimestre2Points = [];
        $trimestre3Points = [];
        $calculator = new PointCalculatorService();
        foreach ($users as $user) {
            $userPoints[$user->getId()] = $calculator->compute($user, $allPoints, $allTrimestres);
        }
        foreach ($userPoints as $id => $userPoint) {
            foreach ($userPoint as $key => $point) {
                if ($key === 1 && $userPoint['visible']) {
                    $trimestre1Points[$id] = round($point['total'], 2);
                } else if ($key === 2 && $userPoint['visible']) {
                    $trimestre2Points[$id] = round($point['total'], 2);
                } else if ($key === 3 && $userPoint['visible']) {
                    $trimestre3Points[$id] = round($point['total'], 2);
                }
            }
        }
        arsort($trimestre1Points);
        arsort($trimestre2Points);
        arsort($trimestre3Points);
        return $this->render('point/index.html.twig', [
            'points1' => $trimestre1Points,
            'points2' => $trimestre2Points,
            'points3' => $trimestre3Points,
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
        $users = $em->getRepository(User::class)->getUsersByName($keyword);
        $allPoints = $em->getRepository(Point::class)->findAll();
        $allTrimestres = $em->getRepository(Trimestre::class)->findAll();
        $userPoints = [];
        $calculator = new PointCalculatorService();
        foreach ($users as $user) {
            $userPoints[$user->getId()] = $calculator->compute($user, $allPoints, $allTrimestres);
            for ($i = 1; $i <= 3; $i++) {
                $userPoints[$user->getId()][$i]['total'] = min($userPoints[$user->getId()][$i]['total'] ?? 0, $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
            }
        }

        return new JsonResponse(array_values($userPoints));
    }

    #[Route('/NJXWQYLONZQWM2LMNFXWY/{id}', name: 'points_responsible_precise', requirements: ['id' => '\d+'])]
    public function getOPENSforUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $allPoints = $em->getRepository(Point::class)->findAll();
        $allTrimestres = $em->getRepository(Trimestre::class)->findAll();
        $calculator = new PointCalculatorService();
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }
        $userPoints = $calculator->compute($user, $allPoints, $allTrimestres);
        $userPoints['rentree'] = $user->getRentree();
        $userPoints[1]['p'] = 0;
        $userPoints[2]['p'] = 0;
        $userPoints[3]['p'] = 0;
        $userPoints[1]['o'] = 0;
        $userPoints[2]['o'] = 0;
        $userPoints[3]['o'] = 0;

        $dates = [1 => [], 2 => [], 3 => []];
        for ($i = 1; $i <= 3; $i++) {
            foreach ($userPoints[$i]['points'] as $point) {
                if (in_array($point->getReason(), [ "Présence à l'association en présentiel", "Bonne réponse", "Mauvaise réponse", "Image acceptée"])) {
                    $userPoints[$i]['p']+= $point->getPoints();
                    $dates[$i][] = ['date' =>$point->getDate()->format('d/m/Y'), 'points'=>$point->getPoints()];
                } else {
                    $userPoints[$i]['o']+= $point->getPoints();
                }
            }
        }
        $userPoints[1]['realTotal'] = min($userPoints[1]['total'], $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
        $userPoints[2]['realTotal'] = min($userPoints[2]['total'], $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
        $userPoints[3]['realTotal'] = min($userPoints[3]['total'], $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
        dump($userPoints, $dates);
        return $this->render('point/point_detail.html.twig', [
            'point' => $userPoints,
            'dates' => $dates
        ]);
    }

}
