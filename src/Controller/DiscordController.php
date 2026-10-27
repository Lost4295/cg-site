<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CompleteFormType;
use App\Repository\UserRepository;
use App\Service\DiscordApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
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
use Throwable;

final class DiscordController extends AbstractController
{
    public function __construct(
        private readonly DiscordApiService $discordApiService,
        private readonly Security          $security
    )
    {
    }

    /**
     * @throws RandomException
     */
    #[Route('/discord/connect', name: 'oauth_discord', methods: ['POST'])]
    public function connect(Request $request, LoggerInterface $logger): Response
    {
        if (!$this->isCsrfTokenValid('discord-auth', $request->request->get('token'))) {
            throw $this->createAccessDeniedException();
        }
        $state = bin2hex(random_bytes(32));
        $request->getSession()->set('discord_oauth_state', $state);

        $logger->info('Discord OAuth connect', [
            'session_id' => $request->getSession()->getId(),
            'state_saved' => $request->getSession()->has('discord_oauth_state'),
        ]);
        $scope = ['identify', 'email'];
        return $this->redirect($this->discordApiService->getAuthorizationUrl($scope, $state));
    }

    #[Route('/discord/finish', name: 'finish_login', methods: ['GET', 'POST'])]
    public function finish(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(CompleteFormType::class, $user );
        $form->handleRequest($request);
        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            $user->setAccountValid(User::IS_OK);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_home');
        }
        return $this->render('discord/auth.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
//    #[Route('/discord/auth', name: 'oauth_discord_auth')]
//    public function auth(EntityManagerInterface $em, Request $request, UserRepository $userRepo): Response
//    {
//        if (!$request->get('accessToken')) {
//            return new Response("Bad Request", 400);
//        }
//        $accessToken = $request->get('accessToken');
//        // TODO check si tout profile est ok sinon form pour
//        $discordUser = $this->discordApiService->fetchUser($accessToken);
//
//        $user = $userRepo->find($discordUser->id);
//        if ($user->getAccountValid() === User::IS_OK) {
//            return $this->redirectToRoute('app_home');
//        }
//        $form = $this->createForm(CompleteFormType::class, $this->getUser());
//        $form->handleRequest($request);
//        if ($request->isMethod('POST')) {
//            if ($form->isSubmitted() && $form->isValid()) {
//
//                $user->setAccountValid(User::IS_OK);
//                $em->persist($user);
//                $em->flush();
//
//                return $this->redirectToRoute('app_home');
//            }
//        }
//        $user = $userRepo->findOneBy(['accessToken' => $request->get('accessToken')]);
//        if (!$user) {
//            return new Response("Bad Request", 400);
//        }
//
//        return $this->render('discord/auth.html.twig', [
//            'form' => $form->createView(),
//        ]);
//    }

    #[Route('/discord/check', name: 'oauth_discord_check')]
    public function check(EntityManagerInterface $em, Request $request, UserRepository $userRepo, LoggerInterface $logger): Response
    {
        $session = $request->getSession();
        $expectedState = $session->get('discord_oauth_state');
        $logger->info('Discord OAuth callback', [
            'session_id' => $request->getSession()->getId(),
            'state_exists' => $request->getSession()->has('discord_oauth_state'),
        ]);
        $receivedState = $request->query->get('state');
        $code = $request->query->get('code');

        // Un état valide ne peut servir qu'une seule fois.
        $session->remove('discord_oauth_state');


        if (
            !is_string($expectedState)
            || !is_string($receivedState)
            || !is_string($code)
            || $code === ''
        ) {
            throw $this->createAccessDeniedException(
                'Retour OAuth Discord invalide.'
            );
        }
        if (!hash_equals($expectedState, $receivedState)) {
            throw $this->createAccessDeniedException(
                'Le state OAuth Discord ne correspond pas.'
            );
        }
        try {
            $accessToken = $this->discordApiService->fetchToken($code);
            $discordUser = $this->discordApiService->fetchUser($accessToken);
        } catch (Throwable $e) {
            throw $this->createAccessDeniedException(
                'La connexion Discord a échoué.'
            );
        }
        $user = $userRepo->find($discordUser->id);
        if (!$user) {
            $user = new User();
            $user->setId($discordUser->id);
            $user->setAccountValid(User::MISSING_DATA);
            $user->setNom("Nom à définir");
            $user->setPrenom("Prénom à définir");
            $user->setClasse("Classe");
            $user->setDateInscr(new DateTime());
            $user->setVisibility(false);
            $user->setIsAdmin(false);
        }

        $user->setPseudo($discordUser->username);
        $user->setEmail($discordUser->email);
        $user->setAvatar($discordUser->avatar);

        $em->persist($user);
        $em->flush();

        $this->security->login($user);
        if ($user->getAccountValid() !== User::IS_OK) {
            return $this->redirectToRoute('finish_login');
        }
        return $this->redirectToRoute('app_home');
    }

}
