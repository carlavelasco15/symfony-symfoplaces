<?php

namespace App\Controller;

use App\Entity\Pelicula;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactFormType;
use App\Repository\PeliculaRepository;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class DefaultController extends AbstractController
{

    #[Route('/', name: 'portada')]
    public function index(PeliculaRepository $pR):Response
    {
        return $this->render('index.html.twig', [
            'peliculas' => $pR->findLast($this->getParameter('app.portada_results'))
        ]);
    }


    #[Route('/contact', name: 'contacto')]
    public function contact(
        Request $request,
        MailerInterface $mailer
    ): Response {

        $formulario = $this->createForm(ContactFormType::class);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {

            $datos = $formulario->getData();

            $email = new TemplatedEmail();
            $email
                ->from(new Address($datos['email'], $datos['nombre']))
                ->to($this->getParameter('app.admin_email'))
                ->subject($datos['asunto'])
                ->htmlTemplate('email/contact.html.twig')
                ->context([
                    'de' => $datos['email'],
                    'nombre' => $datos['nombre'],
                    'asunto' => $datos['asunto'],
                    'mensaje' => $datos['mensaje']
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Mensaje enviado correctamente.');
            return $this->redirectToRoute('portada');
        }

        return $this->renderForm('contact.html.twig', [
            'formulario' => $formulario,
            'contact' => "Contacto con plantilla"
        ]);
    }


    #[Route('/contact2', name: 'contacto2')]
    public function contact2(
        Request $request,
        MailerInterface $mailer
    ): Response {

        $formulario = $this->createForm(ContactFormType::class);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {

            $datos = $formulario->getData();

            $email = new Email();
            $email
                ->from($datos['email'])
                ->to($this->getParameter('app.admin_email'))
                ->subject($datos['asunto'])
                ->text($datos['mensaje']);

            $mailer->send($email);

            $this->addFlash('success', 'Mensaje enviado correctamente.');
            return $this->redirectToRoute('portada');
        }

        return $this->renderForm('contact.html.twig', [
            'formulario' => $formulario,
            'contact' => "Contacto normal"
        ]);
    }



}
