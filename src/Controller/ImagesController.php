<?php

namespace App\Controller;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ImagesController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/check', name: 'check_images')]
    public function index(EntityManagerInterface $em): Response
    {
        $images = $em->getRepository(Image::class)->findBy(["verified" => false]);
        return $this->render('images/index.html.twig', [
            'images'=>$images
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/validate/{id:image}', name: 'validate_images')]
    public function validateImage(Image $image,EntityManagerInterface $em): JsonResponse
    {
        $image->setVerified(true);
        $image->setOk(true);
        $em->persist($image);
        $em->flush();
        return new JsonResponse(['status' => 'Image validée']);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user_images/reject/{id:image}', name: 'refuse_images')]
    public function refuseImage(Image $image, EntityManagerInterface $em): Response
    {
        $image->setVerified(true);
        $image->setOk(false);
        $em->persist($image);
        $em->flush();
        return new JsonResponse(['status' => 'Image refusée']);
    }

}
