<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'] )]
    public function delete(Request $request, Post $post, PostRepository $postRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $postRepository->remove($post);
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/upload-file', name: 'app_post_upload', methods: ['POST'] )]
    public function upload(Request $request, Post $post, FileRepository $fileRepository, SluggerInterface $slugger): Response
    {
        $upload = $request->files->get("upload");
        $originalFilename = pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$upload->guessExtension();

        $file = new File();
        $file->setName($upload->getClientOriginalName());
        $file->setPath($newFilename);
        $file->setMimeType($upload->getMimeType());
        $file->setSize($upload->getSize());
        $file->setPost($post);

        // Move the file to the directory where brochures are stored
        $upload->move($this->getParameter('uploads_dir'), $newFilename);

        // Save File
        $fileRepository->add($file);

        return $this->redirectToRoute('app_post_edit', ["id" => $post->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete-file/{fileId}', name: 'app_post_delete-file', methods: ['POST'] )]
    public function deleteFile(Request $request, int $id, int $fileId, FileRepository $fileRepository): Response
    {
        if ($this->isCsrfTokenValid('delete-file'.$fileId, $request->request->get('_token'))) {
            $file = $fileRepository->find($fileId);

            //delete from file-system
            unlink(join(DIRECTORY_SEPARATOR, array($this->getParameter('uploads_dir'), $file->getPath())));

            // delete from db
            $fileRepository->remove($file);
        }

        return $this->redirectToRoute('app_post_edit', ["id" => $id], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/download-file/{fileId}', name: 'app_post_download-file', methods: ['GET'] )]
    public function downloadFile(Request $request, int $id, int $fileId, FileRepository $fileRepository): Response
    {
        if ($this->isCsrfTokenValid('delete-file'.$fileId, $request->request->get('_token'))) {
            $file = $fileRepository->find($fileId);

            //delete from file-system
            unlink(join(DIRECTORY_SEPARATOR, array($this->getParameter('uploads_dir'), $file->getPath())));

            // delete from db
            $fileRepository->remove($file);
        }

        $file = $fileRepository->find($fileId);
        $filePath = join(DIRECTORY_SEPARATOR, array($this->getParameter('uploads_dir'), $file->getPath()));

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getName()
        );

        return $response;
    }
}