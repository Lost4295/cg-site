<?php

namespace App\Controller;

use App\Entity\Question;
use App\Form\QuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class QuestionsController extends AbstractController
{
    #[IsGranted("ROLE_USER")]
    #[Route('/question/add', name: 'add_question')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $question->setUser($this->getUser());
            $question->setValid(false);
            $question->setVerified(false);
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Votre question a bien été enregistrée.');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('questions/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/questions/check', name: 'check_questions')]
    public function checkQuestions(EntityManagerInterface $em): Response
    {
        $questions = $em->getRepository(Question::class)->findBy(["verified" => false]);
        return $this->render('questions/check.html.twig', [
            'questions' => $questions
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/questions/validate/{id:question}', name: 'validate_questions')]
    public function validateQuestion(Question $question,EntityManagerInterface $em): JsonResponse
    {
        $question->setVerified(true);
        $question->setValid(true);
        $em->persist($question);
        $em->flush();
        return new JsonResponse(['status' => 'Question validée']);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/questions/reject/{id:question}', name: 'refuse_questions')]
    public function refuseQuestion(Question $question, EntityManagerInterface $em): Response
    {
        $question->setVerified(true);
        $question->setValid(false);
        $em->persist($question);
        $em->flush();
        return new JsonResponse(['status' => 'Question refusée']);
    }


}
