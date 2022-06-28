<?php

namespace App\Controller;

use App\Entity\Place;
use App\Form\DeletePlaceFormType;
use App\Form\PlaceType;
use App\Service\PaginatorService;
use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/place')]
class PlaceController extends AbstractController
{
    #[Route('s/{pagina}', defaults: ['pagina'=>1], name: 'place_list', methods: ['GET'])]
    public function index(
        int $pagina=1,
        PlaceRepository $placeRepository,
        PaginatorService $paginator): Response
    {

        $paginator->setEntityType('App\Entity\Place');
        $places = $paginator->findAllEntities($pagina);

        return $this->render('place/index.html.twig', [
            'places' => $places,
            'paginator' => $paginator
        ]);
    }

    #[Route('/create', name: 'place_create')]
    public function create(
        Request $request,
        PlaceRepository $placeRepository): Response
    {

        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            /* $actor->setUser($this->getUser()); */

            $placeRepository->add($place, true);
            $this->addFlash('success', 'Lugar guardado con éxito.');

            return $this->redirectToRoute('place_show', ['id' => $place->getId()]);
        }

        return $this->render('place/create.html.twig', [
            'formulario' => $form->createView(),
        ]);
    }


    #[Route('/edit/{id}', name: 'place_edit')]
    public function edit (
        Place $place,
        Request $request,
        PlaceRepository $placeRepository): Response
    {

        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $placeRepository->add($place, true);
            $this->addFlash('success', 'Lugar actualizado con éxito.');

            return $this->redirectToRoute('place_show', ['id' => $place->getId()]);
        }

        return $this->render('place/edit.html.twig', [
            'formulario' => $form->createView(),
            'place' => $place
        ]);
    }


    #[Route('/delete/{id}', name: 'place_delete')]
    public function delete(
        Place $place,
        Request $request,
        PlaceRepository $placeRepository
        ): Response
    {

        $form = $this->createForm(DeletePlaceFormType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $placeRepository->remove($place, true);
            $this->addFlash('success', "S'ha eliminat el lloc (i les imatges i comentaris relacionats) correctament.");

            return $this->redirectToRoute('place_list');
        }

        return $this->render('place/delete.html.twig', [
            'formulario' => $form->createView(),
            'place' => $place
        ]);
    }


    #[Route('/show/{id}', name: 'place_show')]
    public function show(
        Place $place): Response
    {
        return $this->render('place/show.html.twig', [
            'place' => $place
        ]);
    }



}
