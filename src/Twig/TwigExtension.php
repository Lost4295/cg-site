<?php

namespace App\Twig;

use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;
use App\Service\PointCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{

    public EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getuser', [$this, 'getuser']),
            new TwigFunction('getpts', [$this, 'getpts']),
            new TwigFunction('getuserini', [$this, 'getuserini']),
        );
    }


    public function getuser($path)
    {
        $user = $this->em->getRepository(User::class)->find($path);
        return ucfirst($user->getPrenom()) . ' ' . ucfirst($user->getNom());
    }
    public function getuserini($path)
    {
        $user = $this->em->getRepository(User::class)->find($path);
        return ucfirst(substr($user->getPrenom(),0,1)) . ucfirst(substr($user->getNom(),0,1));
    }

    public function getpts($id)
    {
        $allPoints = $this->em->getRepository(Point::class)->findAll();
        $allTrimestres = $this->em->getRepository(Trimestre::class)->findAll();
        $calculator = new PointCalculatorService();
        $user = $this->em->getRepository(User::class)->find($id);
        $userPoints = $calculator->compute($user, $allPoints, $allTrimestres);
        return [
            1=>$userPoints[1]['total'],
            2=>$userPoints[2]['total'],
            3=>$userPoints[3]['total'],
        ];
    }
}
