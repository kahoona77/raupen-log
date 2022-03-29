<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/post')]
class PostController extends AbstractController
{

    #[Route('/{id}', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, PostRepository $postRepository): Response
    {
        $post = new Post();
        if ($id > 0) {
            $post = $postRepository->find($id);
        }

        $post->setDate(new \DateTime());
        $post->setUser($this->getUser());

        if ($request->getMethod() == "POST") {
            $post->setTitle($request->request->get("title"));
            $post->setTitle($request->request->get("title"));
            $post->setBody($request->request->get("body"));

            $postRepository->add($post);
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_post_delete', methods: ['POST'] )]
    public function delete(Request $request, Post $post, PostRepository $postRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $postRepository->remove($post);
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}