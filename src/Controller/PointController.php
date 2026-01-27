<?php

namespace App\Controller;

use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;
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
        $users = $em->getRepository(User::class)->getUsersByName($keyword);
        $userIds = array_map(static function (User $user) {
            return $user->getId();
        }, $users);
        /** @var Point[] $points */
        $points = $em->getRepository(Point::class)->findByIds($userIds);
        /** @var Trimestre[] $trimestres */
        $trimestres = $em->getRepository(Trimestre::class)->findAll();
        $userPoints = [];
        foreach ($points as $point) {
            $classe = $point->getUser()->getOnlyClasse();
            $trims = array_filter($trimestres, function ($trimestre) use ($classe) {
                return $trimestre->getNiveau() === $classe;
            });
            foreach ($trims as $t) {
                if ($t->getDateDebut() < $point->getDate() && $point->getDate() <= $t->getDateFin()) {
                    $trimestre = $t;
                }
            }
            if (!array_key_exists($point->getUser()->getId(), $userPoints)) {
                $userPoints[$point->getUser()->getId()][$trimestre->getTrimestre()] = $point->getPoints();
            } else {
                if (!array_key_exists($trimestre->getTrimestre(), $userPoints[$point->getUser()->getId()])) {
                    $userPoints[$point->getUser()->getId()][$trimestre->getTrimestre()] = 0;
                }
                $userPoints[$point->getUser()->getId()][$trimestre->getTrimestre()] += $point->getPoints();
            }
        }
        $formattedPoint = [];
        foreach ($userPoints as $key => $point) {
            $array = [];
            $user = array_find($users, fn($user) => $user->getId() == $key);
            if ($user) {
                $array['r1'] = min($point[2] ?? 0, $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
                $array['r2'] = min($point[3] ?? 0, $user->getIsAdmin() == 0 ? 4 : ($user->getIsAdmin() == 1 ? 6 : 8));
                $array['id'] = $user->getId();
                $array['name'] = $user->getNom() . " " . $user->getPrenom();
                $array['classe'] = $user->getClasse();
                $array['warning']= $user->getIsAdmin() ? "⚠️":"";
                $formattedPoint[] = $array;
            }
        }

        return new JsonResponse($formattedPoint);
    }

    #[
        Route('/NJXWQYLONZQWM2LMNFXWY/{id}', name: 'points_responsible_precise', requirements: ['id' => '\d+'])]
    public function getOPENSforUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $point = $em->getRepository(Point::class)->getPointsByIdPrecise($id);
        if (!$point) {
            return new Response("Utilisateur non trouvé", 404);
        }
        $user = $em->getRepository(User::class)->find($id);
        $points = [
            'is_admin' => $user->getIsAdmin(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            's1p' => 0,
            's2p' => 0,
            's1o' => 0,
            's2o' => 0
        ];
        foreach ($point as $trimestrePoints) {
            /** @var Point $trimestrePoint */
            foreach ($trimestrePoints as $key => $trimestrePoint) {

                if ($trimestrePoint->getReason() == "Présence à l'association en présentiel") {
                    if ($key == 2) {
                        $points['s1p'] += $trimestrePoint->getPoints();
                    } else {
                        $points['s2p'] += $trimestrePoint->getPoints();
                    }
                } else {
                    if ($key == 2) {
                        $points['s1o'] += $trimestrePoint->getPoints();
                    } else {
                        $points['s2o'] += $trimestrePoint->getPoints();
                    }
                }
            }
        }
        $points['s1p'] = min($points['s1p'], $points['is_admin'] == 0 ? 4 : ($points['is_admin'] == 1 ? 6 : 8));
        $points['s2p'] = min($points['s2p'], $points['is_admin'] == 0 ? 4 : ($points['is_admin'] == 1 ? 6 : 8));
        $points['s1o'] = min($points['s1o'], $points['is_admin'] == 0 ? 4 : ($points['is_admin'] == 1 ? 6 : 8));
        $points['s2o'] = min($points['s2o'], $points['is_admin'] == 0 ? 4 : ($points['is_admin'] == 1 ? 6 : 8));
        return $this->render('point/point_detail.html.twig', [
            'point' => $points,
        ]);
    }

}
