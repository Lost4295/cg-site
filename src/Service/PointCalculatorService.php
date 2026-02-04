<?php

namespace App\Service;

use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;

class PointCalculatorService
{
    public function compute(User $user, array $points, array $trimestres): array
    {
        $result = [];

        /** @var Trimestre[] $trimestres */
        foreach ($trimestres as $t) {
            if ($t->getNiveau() !== $user->getOnlyClasse()) {continue;}
            $result[$t->getTrimestre()] = [
                'points' => [],
                'total' => 0,
                'start' => $t->getDateDebut(),
                'end' => $t->getDateFin(),
            ];
        }
        /** @var Point[] $points */
        foreach ($points as $point) {
            if ($point->getUser()->getId() !== $user->getId()) {continue;}
            $date = $point->getDate();
            foreach ($result as $k => &$quarter) {
                if ($date >= $quarter['start'] && $date < $quarter['end']) {
                    $quarter['points'][] = $point;
                    $quarter['total'] += $point->getPoints();
                    break;
                }
            }
        }
        $result['id'] = $user->getId();
        $result['visible'] = $user->isVisibility();
        $result['name'] = $user->getNom() . " " . $user->getPrenom();
        $result['classe'] = $user->getClasse();
        $result['warning'] = $user->getIsAdmin() ? "⚠️" : "";

        return $result;
    }
}
