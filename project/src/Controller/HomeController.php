<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserService;


class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(UserService $userService): Response
    {
        $message = $userService->getUserInfo();
        $testVariable = ["test1", "test2", "test3"];
        return $this->render('home/index.html.twig', [
            'title' => 'Welcome to the Homepage!',
            'testVariable' => $testVariable,
            'message' => $message,
        ]);
    }
}