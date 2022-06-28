<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    #[Route('/home', name: 'home', methods: ['GET'])]
    public function home(): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('user/home.html.twig');
    }


    #[Route('/user/pic/{picture}', name: 'pic_show', methods: ['GET'])]
    public function showPic(string $picture): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $ruta = $this->getParameter('app.users_pics.root');

        $response = new BinaryFileResponse($ruta.'/'.$picture);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $picture,
            iconv('UTF-8', 'ASCII//TRANSLIT', $picture)
        );

        return $response;
    }
}
