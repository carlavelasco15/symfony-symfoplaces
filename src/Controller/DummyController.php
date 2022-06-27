<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactoFormType;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class DummyController extends AbstractController
{
    #[Route('/contacto', name: 'contact')]
    public function contact(
        Request $request,
        MailerInterface $mailer): Response
    {

        $formulario = $this->createForm(ContactoFormType::class);
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
            return $this->redirectToRoute('place_list');
        }

        return $this->renderForm('contact.html.twig', [
            'formulario' => $formulario,
            'contact' => "Contacto con plantilla"
        ]);
    }
}
