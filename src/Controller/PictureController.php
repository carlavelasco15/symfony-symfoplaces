<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PictureRepository;
use App\Entity\Picture;
use App\Service\FileService;
use App\Form\DeletePictureFormType;
use App\Form\PictureType;

#[Route('/picture')]
class PictureController extends AbstractController
{
    #[Route('s/', name: 'picture_list', methods: ['GET'])]
    public function list(PictureRepository $pictureRepository): Response
    {
        return $this->render('picture/index.html.twig', [
            'places' => $pictureRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'picture_create')]
    public function create(
        Request $request,
        PictureRepository $pictureRepository,
        FileService $uploader): Response
    {

        $place = new Picture();
        $form = $this->createForm(PictureType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('picture')->getData();

            if($file)
                $place->setPicture($uploader->upload($file));

            /* $actor->setUser($this->getUser()); */

            $pictureRepository->add($place, true);
            $this->addFlash('success', 'Imagen guardada con éxito.');

            return $this->redirectToRoute('picture_show', ['id' => $place->getId()]);
        }

        return $this->render('picture/create.html.twig', [
            'formulario' => $form->createView(),
        ]);
    }


    #[Route('/edit/{id}', name: 'picture_edit')]
    public function edit (
        Picture $place,
        Request $request,
        PictureRepository $pictureRepository,
        FileService $uploader): Response
    {
        $fichero = $place->getPicture();
        
        $form = $this->createForm(PictureType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('picture')->getData();

            if($file)
                $fichero = $uploader->replace($file, $fichero);
            
            $place->setPicture($fichero);

            $pictureRepository->add($place, true);
            $this->addFlash('success', 'Imagen actualizada con éxito.');

            return $this->redirectToRoute('picture_show', ['id' => $place->getId()]);
        }

        return $this->render('picture/edit.html.twig', [
            'formulario' => $form->createView(),
            'place' => $place
        ]);
    }


    #[Route('/delete/{id}', name: 'picture_delete')]
    public function delete(
        Picture $place,
        Request $request,
        PictureRepository $pictureRepository,
        FileService $uploader): Response
    {
        $form = $this->createForm(DeletePictureFormType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            if($place->getPicture())
                $uploader->remove($place->getPicture());

            $pictureRepository->remove($place, true);
            $this->addFlash('success', "Se ha eliminado la imagen correctamente.");

            return $this->redirectToRoute('picture_list');
        }

        return $this->render('picture/delete.html.twig', [
            'formulario' => $form->createView(),
            'place' => $place
        ]);
    }


    #[Route('/show/{id}', name: 'picture_show')]
    public function show(
        Picture $place): Response
    {
        return $this->render('picture/show.html.twig', [
            'place' => $place
        ]);
    }
}
