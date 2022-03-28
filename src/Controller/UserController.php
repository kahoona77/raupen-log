<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, UserRepository $userRepository): Response
    {
        $user = new User();
        if ($id > 0) {
            $user = $userRepository->find($id);
        }

        if ($request->getMethod() == "POST") {
            $user->setUsername($request->request->get("username"));
            $user->setDisplayName($request->request->get("display-name"));

            $isAdmin = $request->request->get("is-admin");
            $user->setAdmin($isAdmin === "on");

            if (!$user->getId()) {
                // new user set password
                $password = $request->request->get("password");
                $passwordRepeat = $request->request->get("password-repeat");

                if ($password != $passwordRepeat) {
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'error' => "Die Passwörter sind nicht gleich!",
                    ]);
                }

                $hashedPassword = $userRepository->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }


            $userRepository->add($user);
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_user_delete', methods: ['POST'] )]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
