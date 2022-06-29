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
use App\Repository\PlaceRepository;

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
        FileService $uploader,
        PlaceRepository $placeRepository): Response
    {

        $picture = new Picture();
        $form = $this->createForm(PictureType::class, $picture);
        $form->handleRequest($request);
            
        $place_id = $request->request->get('place_id');
        $place = $placeRepository->find($place_id);

        if($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('picture')->getData();

            if($file)
                $picture->setPicture($uploader->upload($file));

            $picture->setPlace($place);

            $pictureRepository->add($picture, true);
            $this->addFlash('success', 'Imagen guardada con éxito.');
            
        }else{
            $this->addFlash('error', 'No se pudo añadir la imagen.');
        }

        return $this->redirectToRoute('place_edit', ['id' => $picture->getPlace()->getId()]);
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

            return $this->redirectToRoute('place_edit', ['id' => $place->getPlace()->getId()]);
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

            $id_place = $place->getPlace()->getId();

            $pictureRepository->remove($place, true);
            $this->addFlash('success', "Se ha eliminado la imagen correctamente.");

            return $this->redirectToRoute('place_edit', [
                'id' => $id_place
            ]);
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

    #[Route('/show/{id}', name: 'picture_delete_cover')]
    public function deleteImagen(
        Picture $place): Response
    {
        return $this->render('picture/show.html.twig', [
            'place' => $place
        ]);
    }


    #[Route('/picture/cover/delete/{id}', name: 'picture_delete_cover')]
    public function deleteCover(
        Picture $picture,
        Request $request,
        FileService $fileService,
        PictureRepository $pictureRepository
    ):Response {
        
        if($pic = $picture->getPicture()) {
            $fileService->remove($pic);

            $picture->setPicture(NULL);

            $pictureRepository->add($picture, true);

            $this->addFlash('success', 'La carátula de ' . $picture->getTitle() . ' fue borrada.');
        }
        
            return $this->redirectToRoute('picture_edit', [
                'id' => $picture->getId()
                ]);
    }
}
