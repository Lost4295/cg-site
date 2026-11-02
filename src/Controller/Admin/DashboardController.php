<?php

namespace App\Controller\Admin;

use App\Entity\GuildInformation;
use App\Entity\User;
use App\Form\AddAdminType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
            ->setFaviconPath('logo.png');
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
        yield MenuItem::linkTo(DateCrudController::class, 'Dates des sessions', 'fas fa-calendar');
        yield MenuItem::linkTo(PointCrudController::class, 'Points des utilisateurs', 'fas fa-list');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-users');
        yield MenuItem::linkToRoute('Ajouter un utilisateur comme administrateur', 'fas fa-user-plus', 'add_admin_user');
        yield MenuItem::linkTo(EventCrudController::class, 'Événements', 'fas fa-bars');
    }


    #[Route('/add/admin', name: 'add_admin_user')]
    public function dashboardUsers(Request $request, EntityManagerInterface $em): Response
    {

        $form = $this->createForm(AddAdminType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var User $user */
            $user = $data['user'];
            $user->addRole('ROLE_GUILD_HANDLER');
            /** @var GuildInformation $guild */
            foreach ($data['guilds'] as $guild) {
                $user->setGuildIds([...$user->getGuildIds() ,$guild->getGuildId()]);
            }
            $em->flush();
            $this->addFlash('success', 'Enregistrement réussi !');
        }
        return $this->render('admin/addadmin.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
