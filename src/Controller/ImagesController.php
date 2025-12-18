<?php

namespace App\Controller;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ImagesController extends AbstractController
{
    private string $discordWebhook;
    private HttpClientInterface $client;
    public function __construct(string $discordWebhook, HttpClientInterface $client)
    {
        $this->discordWebhook = $discordWebhook;
        $this->client = $client;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/check', name: 'check_images')]
    public function index(EntityManagerInterface $em): Response
    {
        $images = $em->getRepository(Image::class)->findBy(["verified" => false]);
        return $this->render('images/index.html.twig', [
            'images'=>$images
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/validate/{id:image}', name: 'validate_images')]
    public function validateImage(Image $image,EntityManagerInterface $em): JsonResponse
    {
        $image->setVerified(true);
        $image->setOk(true);
        $em->persist($image);
        $em->flush();
        $id =$image->getUser()->getId();

        $this->client->request("POST",$this->discordWebhook,[
            'json'=> ["content"=>"<@$id>, votre image a été validée. Vous avez gagné 0.25 points !"]
        ]);

        return new JsonResponse(['status' => 'Image validée']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/reject/{id:image}', name: 'refuse_images')]
    public function refuseImage(Image $image, EntityManagerInterface $em): Response
    {
        $image->setVerified(true);
        $image->setOk(false);
        $em->persist($image);
        $em->flush();
        $id =$image->getUser()->getId();
        $this->client->request("POST",$this->discordWebhook,[
            'json'=> ["content"=>"<@$id>, votre image n\'a pas été validée. Vous n\'avez donc pas gagné de point."]
        ]);
        return new JsonResponse(['status' => 'Image refusée']);
    }

}
