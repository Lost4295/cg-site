<?php

namespace App\Twig;

use App\Entity\Point;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
        $user = $this->em->getRepository(Point::class)->getPointsById($id);
        return $user;
    }
}
