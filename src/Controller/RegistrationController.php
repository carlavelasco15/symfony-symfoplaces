<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\Service\FileService;
use Psr\Log\LoggerInterface;
use App\Form\UserDeleteFormType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, FileService $uploader): Response
    {
        $user = new User();
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

                
            $uploader->setTargetDirectory($this->getParameter('app.users_pics.root'));
            
            $file = $form->get('picture')->getData();

            if($file)
                $user->setPicture($uploader->upload($file));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@symfofilms.com', 'Registro de usuarios'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('email/register_verification.html.twig')
            );
            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('home');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('home');
    }

    #[Route('/resendverificationemail', name: 'resend_verification', methods: ['GET'])]
    public function resendVerificationEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
            ->from(new Address(
                    'no-reply@symfofilms.robertsallent.com',
                    'Registro de usuarios'))
            ->to($user->getEmail())
            ->subject('Por favor, confirma tu email')
            ->htmlTemplate('email/register_verification.html.twig')
            );

            $mensaje = 'Operación realizada, revisa tu email y haz clic en el enlace para completar la operación de registro. El mensaje de advertencia desaparecerá tras completar el proceso';

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', $mensaje);

        return $this->redirectToRoute('home');
    }

    #[Route('/unsuscribe', name: 'unsuscribe', methods: ['GET', 'POST'])]
    public function unsuscribe(Request $request,
                                LoggerInterface $appUserInfoLogger,
                                FileService $uploader,
                                EntityManagerInterface $entityManager,
                                SessionInterface $session,
                                TokenStorageInterface $tokenStorageInterface): Response 
    {

       $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

       $usuario = $this->getUser();

       $formulario = $this->createForm(UserDeleteFormType::class, $usuario);
       $formulario->handleRequest($request);

       if($formulario->isSubmitted() && $formulario->isValid()) {
           $uploader->setTargetDirectory($this->getParameter('app.users_pics.root'));

           if($usuario->getPicture())
                $uploader->remove($usuario->getPicture());


            /* foreach ($usuario->getPeliculas() as $pelicula) {
                $usuario->removePelicula($pelicula);
            } */

            
            $entityManager->remove($usuario);
            $entityManager->flush();

            $tokenStorageInterface->setToken(NULL);
            $session->invalidate();

            $mensaje = 'Usuario ' . $usuario->getDisplayname(). ' eliminado correctament.';
            $this->addFlash('success', $mensaje);

            $mensaje = 'Usuario ' . $usuario->getDisplayname(). ' se ha dado de baja.';
            $appUserInfoLogger->warning($mensaje);


            return $this->redirectToRoute('portada');
        }



        return $this->renderForm('user/delete.html.twig', [
            "formulario" => $formulario,
            "usuario" => $usuario
        ]);
    }

}
