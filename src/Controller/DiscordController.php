<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CompleteFormType;
use App\Repository\UserRepository;
use App\Service\DiscordApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class DiscordController extends AbstractController
{
    public function __construct(
        private readonly DiscordApiService $discordApiService,
        private readonly Security          $security
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/discord/auth', name: 'oauth_discord_auth')]
    public function auth(EntityManagerInterface $em, Request $request, UserRepository $userRepo): Response
    {
        if (!$request->get('accessToken')) {
            return new Response("Bad Request", 400);
        }
        $accessToken = $request->get('accessToken');
        // TODO check si tout profile est ok sinon form pour
        $discordUser = $this->discordApiService->fetchUser($accessToken);

        $user = $userRepo->find($discordUser->id);
        if ($user->getAccountValid() === User::IS_OK) {
            return $this->redirectToRoute('app_home');
        }
        $form = $this->createForm(CompleteFormType::class, $this->getUser());
        $form->handleRequest($request);
        if ($request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {

                $user->setAccountValid(User::IS_OK);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('app_home');
            }
        }
        $user = $userRepo->findOneBy(['accessToken' => $request->get('accessToken')]);
        if (!$user) {
            return new Response("Bad Request", 400);
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

        $user = $userRepo->find($discordUser->id);

        if ($user) {
            $user->setAccessToken($accessToken);
            $user->setExpiresIn((new DateTime())->modify('+5 days'));
        } else {
            $user = new User();
            $user->setId($discordUser->id);
            $user->setAccessToken($accessToken);
            $user->setPseudo($discordUser->username);
            $user->setEmail($discordUser->email);
            $user->setAvatar($discordUser->avatar);
            $user->setAccountValid(User::MISSING_DATA);
            $user->setExpiresIn(new DateTime("+10 hours"));
            $user->setNom("Nom à définir");
            $user->setPrenom("Prénom à définir");
            $user->setClasse("Classe");
            $user->setWarns(0);
            $user->setDateInscr(new DateTime());
            $user->setVisibility(false);
            $user->setIsAdmin(false);
        }
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('oauth_discord_auth', [
            'accessToken' => $accessToken
        ]);
    }

}
