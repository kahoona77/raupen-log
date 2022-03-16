<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function home(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();

        return $this->render('home.html.twig', [
            'posts' => $posts
        ]);
    }
}