<?php

namespace App\Service;

use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;

class PointCalculatorService
{
    public function compute(User $user, array $points, array $trimestres, string $guildId = '510739348069679115'): array
    {
        $result = [];

        /** @var Trimestre[] $trimestres */
        foreach ($trimestres as $t) {
            if ($t->getNiveau() !== $user->getOnlyClasse()) {continue;}
            $result[$t->getTrimestre()] = [
                'points' => [],
                'total' => 0,
                'total_guild' => 0.0,
                'start' => $t->getDateDebut(),
                'end' => $t->getDateFin(),
            ];
        }
        /** @var Point[] $points */
        foreach ($points as $point) {
            if ($point->getUser()->getId() !== $user->getId()) {continue;}
            $date = $point->getDate();
            foreach ($result as $k => &$quarter) {
                if ($date >= $quarter['start'] && $date <= $quarter['end']) {
                    $quarter['points'][] = $point;
                    $quarter['total'] += $point->getPoints();
                    if ($point->getGuildId() === $guildId) {
                        $quarter['total_guild'] += (float) $point->getPoints();
                    }
                    break;
                }
            }
            unset($quarter);
        }
        $result['id'] = $user->getId();
        $result['visible'] = $user->isVisibility();
        $result['name'] = $user->getNom() . " " . $user->getPrenom();
        $result['classe'] = $user->getClasse();
        $result['warning'] = $user->getIsAdmin() ? "⚠️" : "";
        $result['is_janvier'] =$user->isJanvier();

        return $result;
    }
}
