<?php

namespace App\Controller\Admin;

use App\Entity\Date;
use App\Entity\Event;
use App\Entity\Point;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {

         return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cg Site')
            ->setFaviconPath('assets/favicon.png')
            ;
    }
    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $url = $this->generateUrl('app_home');
        return parent::configureUserMenu($user)
            ->setName($user)

            ->setAvatarUrl($user->getAvatar())
            ->addMenuItems([
                MenuItem::linkToUrl('Retour à la page d\'accueil', 'fa fa-home', $url),
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Dates des sessions', 'fas fa-calendar', Date::class);
        yield MenuItem::linkToCrud('Points des utilisateurs', 'fas fa-list', Point::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Événements', 'fas fa-bars', Event::class);
    }
}
