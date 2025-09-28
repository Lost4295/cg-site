<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CompleteFormType;
use App\Repository\UserRepository;
use App\Service\DiscordApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;

final class DiscordController extends AbstractController
{
    public function __construct(
        private readonly DiscordApiService $discordApiService
    )
    {
    }

    #[Route('/discord/connect', name: 'oauth_discord', methods: ['POST'])]
    public function connect(Request $request): Response
    {
        $token = $request->request->get('token');

        if ($this->isCsrfTokenValid('discord-auth', $token)) {
            $request->getSession()->set('discord-auth', true);
            $scope = ['identify', 'email'];
            return $this->redirect($this->discordApiService->getAuthorizationUrl($scope));
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/discord/auth', name: 'oauth_discord_auth')]
    public function auth(EntityManagerInterface $em, Request $request, UserRepository $userRepo): Response
    {
        // TODO check si tout profile est ok sinon form pour
        $form = $this->createForm(CompleteFormType::class, $this->getUser());
        $form->handleRequest($request);
        if ($request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $em->getRepository(User::class)->find($this->getUser()->getId());
                $user->setAccountValid(User::IS_OK);
                $em->persist($user);
                $em->flush();
                return $this->redirectToRoute('app_home');
            }
        }
        if (!$request->get('accessToken')){
            return new Response("Bad Request", 400);
        }
        $user = $userRepo->findOneBy(['accessToken' => $request->get('accessToken')]);
        if (!$user){
            return new Response("Bad Request", 400);
        }
        if ($user->getAccountValid() !== User::IS_OK){
            $this->redirectToRoute('oauth_discord_auth');
        }
        return $this->render('discord/auth.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/discord/check', name: 'oauth_discord_check')]
    public function check(EntityManagerInterface $em, Request $request, UserRepository $userRepo): Response
    {
        $accessToken = $request->get('access_token');

        if (!$accessToken) {
            return $this->render('discord/check.html.twig');
        }

        $discordUser = $this->discordApiService->fetchUser($accessToken);

        $user = $userRepo->findOneBy(['discordId' => $discordUser->id]);
        if ($user->getExpiresIn()&& $user->getExpiresIn() < new \DateTimeImmutable()) {
            // Supprimer le token (et forcer reconnexion)
            $user->setAccessToken($accessToken);
            $user->setExpiresIn((new \DateTimeImmutable())->modify('+5 days'));

            $em->persist($user);
            $em->flush();
            $this->addFlash("error", "Votre token a expiré. Veuillez vous reconnecter.");

            // Redirection vers la connexion Discord
            return $this->redirectToRoute('app_login');
        }

        if ($user) {
            return $this->redirectToRoute('oauth_discord_auth', [
                'accessToken' => $accessToken
            ]);
        }

        $user = new User();

        $user->setAccessToken($accessToken);
        $user->setPseudo($discordUser->username);
        $user->setEmail($discordUser->email);
        $user->setAvatar($discordUser->avatar);
        $user->setId($discordUser->id);
        $user->setAccountValid(User::MISSING_DATA);
        $user->setExpiresIn(new DateTime("+10 hours"));
        $user->setNom("Nom à définir");
        $user->setPrenom("Prénom à définir");
        $user->setClasse("Classe");
        $user->setWarns(0);
        $user->setDateInscr(new \DateTime());
        $user->setVisibility(false);
        $user->setIsAdmin(false);
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('oauth_discord_auth', [
            'accessToken' => $accessToken
        ]);
    }

}
