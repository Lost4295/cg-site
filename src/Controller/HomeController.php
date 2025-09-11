<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/connect', name: 'app_connect')]
    public function connect(): Response
    {
        return $this->render('home/connect.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/logout', name: 'app_logout')]
    public function logout(Security $security): Response
    {
        $response = $security->logout();
        return $this->redirectToRoute('app_home');
    }


}
