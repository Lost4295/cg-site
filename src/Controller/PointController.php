<?php

namespace App\Controller;

use App\Entity\GuildInformation;
use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;
use App\Service\PointCalculatorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


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


    #[IsGranted("ROLE_RESPONSIBLE_OPEN")]
    #[Route('/points-open/', name: 'responsible_points')]
    public function responsiblePoints(): Response
    {
        return $this->render('point/opens.html.twig');
    }

    #[IsGranted("ROLE_RESPONSIBLE_OPEN")]
    #[Route('/points-open/get/', name: 'api_points')]
    public function getOPENS(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $keyword = $request->query->get('search', '');
        $users = $em->getRepository(User::class)->getUsersByName($keyword);
        $allPoints = $em->getRepository(Point::class)->findAll();
        $allTrimestres = $em->getRepository(Trimestre::class)->findAll();
        $userPoints = [];
        $calculator = new PointCalculatorService();
        foreach ($users as $user) {
            $id = $user->getId();
            $userPoints[$id] = $calculator->compute($user, $allPoints, $allTrimestres);
            for ($i = 1; $i <= 3; $i++) {
                $userPoints[$id][$i]['total'] = min($userPoints[$id][$i]['total'] ?? 0, 9);
                $userPoints[$id][$i]['total_guild'] = min($userPoints[$id][$i]['total_guild'] ?? 0, 9);
            }
        }

        return new JsonResponse(array_values($userPoints));
    }

    #[Route('/points-open/{id}', name: 'points_responsible_precise', requirements: ['id' => '\d+'])]
    public function getOPENSforUser(int $id, EntityManagerInterface $em): Response
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

        $assos = [];
        for ($i = 1; $i <= 3; $i++) {
            $assos[$i] = [];
            foreach ($userPoints[$i]['points'] as $point) {
                if ($point->getGuildId() && !isset($assos[$i][$point->getGuildId()])) {
                    $assos[$i][$point->getGuildId()] = [];
                }
                $assos[$i][$point->getGuildId()][] = $point;
            }
        }
        $resolved = [];
        for ($i = 1; $i <= 3; $i++) {
            $resolved[$i] = [];
            foreach ($assos[$i] as $guildId => $points) {
                $guild = $em->getRepository(GuildInformation::class)->find($guildId);
                if ($guild) {
                    if (!isset($resolved[$i][$guild->getName()])) {
                        $resolved[$i][$guild->getName()] = 0;
                    }
                    foreach ($points as $point) {
                        $resolved[$i][$guild->getName()] += $point->getPoints();
                    }
                } else {
                    foreach ($points as $point) {
                        $resolved[$i][$guildId] += $point->getPoints();
                    }
                }
            }
        }
        $userPoints[1]['realTotal'] = round(min($userPoints[1]['total'], 9), 2);
        $userPoints[2]['realTotal'] = round(min($userPoints[2]['total'], 9), 2);
        $userPoints[3]['realTotal'] = round(min($userPoints[3]['total'], 9), 2);
        $userPoints[1]['realTotalGuild'] = round(min($userPoints[1]['total_guild'], 9), 2);
        $userPoints[2]['realTotalGuild'] = round(min($userPoints[2]['total_guild'], 9), 2);
        $userPoints[3]['realTotalGuild'] = round(min($userPoints[3]['total_guild'], 9), 2);

        return $this->render('point/point_detail.html.twig', [
            'point' => $userPoints,
            'list' => $resolved
        ]);
    }


    /**
     * @throws JsonException
     */
    #[IsGranted("ROLE_RESPONSIBLE_OPEN")]
    #[Route('/export/json', name: 'export_json')]
    public function exportJson(EntityManagerInterface $em): Response
    {
        $cleaned = $this->createData($em);
        $jsonFile = json_encode($cleaned, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = "ExportOPEN-" . (new DateTime())->format("d-m-y_H:i") . ".json";
        $tempFile = tempnam(sys_get_temp_dir(), 'json');
        file_put_contents($tempFile, $jsonFile);
        return $this->file(new SplFileObject($tempFile), $filename);
    }

    #[IsGranted("ROLE_RESPONSIBLE_OPEN")]
    #[Route('/export/xlsx', name: 'export_xlsx')]
    public function exportXlsx(EntityManagerInterface $em): Response
    {
        $cleaned = $this->createData($em);
        $xlsx = new Spreadsheet();
        $xlsx->removeSheetByIndex(0);
        $sheet = new Worksheet($xlsx, "Points OPEN");
        $xlsx->addSheet($sheet);
        $xlsx->setActiveSheetIndex(0);
        $rotated = $this->rotateData($cleaned);
        $sheet->fromArray($rotated, null, 'A1', true);
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $writer = new Xlsx($xlsx);
        $filename = "ExportOPEN-" . (new DateTime())->format("d-m-y_H:i") . ".xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);
        return $this->file($tempFile, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function createData(EntityManagerInterface $em): array
    {

        $users = $em->getRepository(User::class)->findAll();
        $allPoints = $em->getRepository(Point::class)->findAll();
        $allTrimestres = $em->getRepository(Trimestre::class)->findAll();
        $userPoints = [];
        $cleaned = [];
        $calculator = new PointCalculatorService();
        foreach ($users as $user) {
            $id = $user->getId();
            $userPoints[$id] = $calculator->compute($user, $allPoints, $allTrimestres);
            for ($i = 1; $i <= 3; $i++) {
                $userPoints[$id][$i]['total'] = min($userPoints[$id][$i]['total'] ?? 0, 9);
            }
            $cleaned[$id] = [
                'id' => $userPoints[$id]['id'],
                'name' => $userPoints[$id]['name'],
                'classe' => $userPoints[$id]['classe'],
                1 => [
                    'total' => round($userPoints[$id][1]['total'], 2),
                ],
                2 => [
                    'total' => round($userPoints[$id][2]['total'], 2),
                ],
                3 => [
                    'total' => round($userPoints[$id][3]['total'], 2),
                ]
            ];
        }
        return $cleaned;
    }


    private function rotateData(array $cleaned): array
    {
        $rotated = [];
        $header = ['ID', 'Nom et prénom', 'Classe', 'Trimestre 2', 'Trimestre 3'];
        $rotated[] = $header;
        foreach ($cleaned as $user) {
            $row = [
                $user['id'],
                $user['name'],
                $user['classe'],
                $user[2]['total'] ?? 0,
                $user[3]['total'] ?? 0,
            ];
            $rotated[] = $row;
        }
        return $rotated;
    }
}
