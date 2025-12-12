<?php

namespace App\Controller;

use App\Entity\Date;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DateController extends AbstractController
{
    #[Route('/dates', name: 'app_dates')]
    public function index(): Response
    {
        return $this->render('date/index.html.twig', [
        ]);
    }


    /**
     * @throws RandomException
     */
    #[Route('/get_dates', name: 'get_dates')]
    public function getDates(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start= $request->query->get('start');
        $end= $request->query->get('end');

        if (!$start || !$end) {
            return new JsonResponse([],400);
        }
        $dates = $em->getRepository(Date::class)->findDateBetween($start, $end);

        $array = [];
        /** @var Date $date */
        foreach ($dates as $date) {
            $color = "#". ($date->isDistanciel()?'bada55':'c0ffee');
            $array[] = [
                        "id"=> $date->getId(),
                        "title"=> $date->getTitle().(' ('.($date->isDistanciel()?'Distanciel':'Présentiel').')'),
                        "start"=> $date->getDate()->format("Y-m-d H:i:s"),
                        "end"=> $date->getDate()->modify('+6 hours')->format("c"),
                        "rendering"=> 'background',
                        "color"=> $color,
                        "backgroundColor"=> $color
            ];
        }
        return new JsonResponse($array);

    }

    /**
     * @throws RandomException
     */
    public function getDatesBG(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start= $request->query->get('start');
        $end= $request->query->get('end');

        if (!$start || !$end) {
            return new JsonResponse([],400);
        }
        $dates = $em->getRepository(Date::class)->findDateBetween($start, $end);

        $array = [];
        foreach ($dates as $date) {
            $color = "#". ($date->isDistanciel()?'bada55':'c00fee');
            $array[] = [
                "start"=> $date->getDate()->format("Y-m-d H:i:s")(),
                "end"=> $date->getDate()->modify('+6 hours')->format("Y-m-d H:i:s"),
                "display"=> 'background',
                "color"=> $color,
            ];
        }
        return new JsonResponse($array);

    }



}
