<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UsersRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/oubli-pass', name: 'forgotten_password')]
    public function forgottenPassword(Request $request,
        UsersRepository $usersRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        SendMailService $mail
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() )
        {
            // On va chercher l'utilisateur par son E-mail
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());

            if($user){
                // On génère un Token de réinitialisation 
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $entityManager->persist($user);
                $entityManager->flush();

                // On génère un lien de réinitialisation
                $url = $this->generateUrl('reset_pass', ['token'=>$token] ,UrlGeneratorInterface::ABSOLUTE_URL);

                // On crée les donnée du mail
                $context = compact('url','user');

                // On envoie le mail 
                    $mail->Send(
                        'no-reply@E-commerce.fr',
                        $user->getEmail(),
                        'Réinitialisation du Mot de Passe',
                        'password_reset',
                        $context
                    );
                    $this->addFlash("success","E-mail envoyé avec succès");
                    return $this->redirectToRoute('app_login');

            }
            $this->addFlash('danger','Un problème est survenu');
            return $this->redirectToRoute('app_login');

        }
        return $this->render("/Security/reset_password_request.html.twig",
            [
                "requestPassForm" => $form->createView()
            ]
        );
    }

    #[Route(path:'/oubli-pass/{token}' , name:'reset_pass')]
    public function resetPass(string $token,
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ) : Response
    {
        // On vérifie si on a ce token dans la BDD 
        $user = $usersRepository->findOneByResetToken($token);
        if($user){
            $form = $this->createForm(ResetPasswordFormType::class);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) { 
                $user->setResetToken('');
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $em->persist($user);
                $em->flush();

                $this->addFlash(
                   'success',
                   'Mot de passe changé avec success'
                );
                return $this->redirectToRoute('app_login');
            }
            return $this->render("security/reset_password.html.twig",[
                    'passForm' => $form->createView(),
                ]
            );
        }
        $this->addFlash(
           'danger',
           'Jeton Invalide'
        );
        return $this->redirectToRoute('app_login');
    }
}
