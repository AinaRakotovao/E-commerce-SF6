<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        UserAuthenticatorInterface $userAuthenticator, 
        UsersAuthenticator $authenticator, 
        EntityManagerInterface $entityManager,
        SendMailService $mail,
        JWTService $jwt
    ): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            // On génère le JWT de l'utilisateur 
            // On crée le Header 
                $header = [
                    "typ" => "JWT",
                    "alg" => "HS256",
                ];

            // On crée le Payload 
                $payload = [
                    "user_id" => $user->getId(),
                ];

            // On génère le Token 
                $token = $jwt->generate($header, $payload, $this->getParameter("app.jwtsecret") );

            // On envoye un mail
                $mail->send(
                    'no-reply@monsite.net',
                    $user->getEmail(),
                    'Activation de votre compte sur le site E-commerce',
                    "register",
                    compact('user','token')
                );
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name:'verify_user')]
    public function verifyUser($token, 
        JWTService $jwt,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ) : response
    {

        // On vérifie si le Token est valide, n'a pas éxpiré, et n'a pas été modifié 

        if($jwt->isValid($token) && !$jwt->isExpiredToken($token) && $jwt->check($token,$this->getParameter('app.jwtsecret'))){
            // On récupère le Payload 
            $payload = $jwt->getPayload($token);

            // On récupère le User
            $user = $usersRepository->find($payload['user_id']);

            // On vérifie que l'utilisateur existe et n'a pas encore activé son compte 
            if ($user && !$user->isIsVerified()) {
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success', 'Utilisateur Activé');
                return $this->redirectToRoute('profile_index');
            }
        }

        // Ici un problème se pose dans le Token 
        $this->addFlash('danger', 'Le Token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoieverif', name:'resend_verif')]
    public function resendVerif(
        JWTService $jwt,
        SendMailService $mail,
        UsersRepository $usersRepository
    ) : response
    {
        $user = $this->getUser();
        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }

        if($user->isIsVerified()){
            $this->addFlash('warning', 'Cet utilisateur est déjà activé');
            return $this->redirectToRoute('profile_index');
        }

        // On génère le JWT de l'utilisateur 
            // On crée le Header 
            $header = [
                "typ" => "JWT",
                "alg" => "HS256",
            ];

        // On crée le Payload 
            $payload = [
                "user_id" => $user->getId(),
            ];

        // On génère le Token 
            $token = $jwt->generate($header, $payload, $this->getParameter("app.jwtsecret") );

        // On envoye un mail
        $mail->send(
            'no-reply@monsite.net',
            $user->getEmail(),
            'Activation de votre compte sur le site E-commerce',
            "register",
            compact('user','token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('profile_index');
    }
}
