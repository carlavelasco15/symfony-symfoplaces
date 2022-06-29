<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactoFormType;
use App\Repository\PlaceRepository;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class DefaultController extends AbstractController
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
            return $this->redirectToRoute('portada');
        }

        return $this->renderForm('contact.html.twig', [
            'formulario' => $formulario,
        ]);
    }

    #[Route('/', name: 'portada')]
    public function index(PlaceRepository $pR):Response
    {
        return $this->render('index.html.twig', [
            'places' => $pR->findLast($this->getParameter('app.portada_results'))
        ]);
    }
   
}
