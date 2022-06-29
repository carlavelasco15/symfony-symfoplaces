<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Place;
use App\Form\DeletePlaceFormType;
use App\Form\PictureType;
use App\Form\CommentType;
use App\Form\PlaceType;
use App\Form\SearchFormType;
use App\Repository\PictureRepository;
use App\Service\PaginatorService;
use App\Repository\PlaceRepository;
use App\Service\FileService;
use App\Service\SimpleSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Comment;
use App\Repository\CommentRepository;

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
        PlaceRepository $placeRepository,
        LoggerInterface $appInfoLogger): Response
    {

        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $place->setUser($this->getUser());

            $placeRepository->add($place, true);
            $mensaje = "Lugar ". $place->getId() . " guardado con éxito.";
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje);
            return $this->redirectToRoute('place_show', ['id' => $place->getId()]);
        }

        return $this->render('place/create.html.twig', [
            'formulario' => $form->createView(),
        ]);
    }


    #[Route('/search', name: 'place_search', methods: ['GET', 'POST'])]
    public function search(Request $request, SimpleSearchService $busqueda): Response
    {
        
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'Nombre' => 'name',
                'Tipo' => 'type',
                'Valoración' => 'valoration',
                'País' => 'country',
                'Ciudad' => 'village'
            ],
            'order_choices' => [
                'ID' => 'id',
                'Título' => 'titulo',
                'Nombre' => 'name',
                'País' => 'country',
                'Ciudad' => 'village',
            ]
            ]);

        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);

        $formulario->handleRequest($request);

        $places = $busqueda->search('App\Entity\Place');

        return $this->renderForm("place/buscar.html.twig", [
            "formulario" => $formulario,
            "places" => $places
        ]);
    }


    #[Route('/edit/{id}', name: 'place_edit')]
    public function edit (
        Place $place,
        Request $request,
        PlaceRepository $placeRepository): Response
    {

        $formPlace = $this->createForm(PlaceType::class, $place);
        $formPlace->handleRequest($request);

        $picture = new Picture();
        $formPicture = $this->createForm(PictureType::class, $picture);

        if($formPlace->isSubmitted() && $formPlace->isValid()) {

            $placeRepository->add($place, true);
            $this->addFlash('success', 'Lugar actualizado con éxito.');

            return $this->redirectToRoute('place_show', ['id' => $place->getId()]);
        }
        

        return $this->render('place/edit.html.twig', [
            'formularioPlace' => $formPlace->createView(),
            'formularioPicture' => $formPicture->createView(),
            'place' => $place,
        ]);
    }


    #[Route('/delete/{id}', name: 'place_delete')]
    public function delete(
        Place $place,
        Request $request,
        PlaceRepository $placeRepository,
        PictureRepository $pictureRepository,
        FileService $uploader
        ): Response
    {

        $form = $this->createForm(DeletePlaceFormType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            foreach ($place->getPhoto() as $picture) {

                if($picture->getPicture())
                    $uploader->remove($picture->getPicture());

                $pictureRepository->remove($picture, true);
            }

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
        Place $place,
        Request $request,
        CommentRepository $commentRepository): Response
    {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if($commentForm->isSubmitted() && $commentForm->isValid()) {

            $commentRepository($comment, true);
        }

        return $this->render('place/show.html.twig', [
            'place' => $place,
            'commentForm' => $commentForm->createView()
        ]);
    }

    #[Route('/picture/add/{id<\d+>}', name: 'place_picture_add')]
    public function addPicture(Place $place,
                            Request $request,
                            EntityManagerInterface $em,
                            LoggerInterface $appInfoLogger):Response 
    {

        $picture = new Picture();
        $formularioPicture = $this->createForm(PictureType::class, $picture);
        $formularioPicture->handleRequest($request);

        if($formularioPicture->isSubmitted() && $formularioPicture->isValid()) {

            $picture->setPlace($place);

            $em->persist($picture);
            $em->flush();

            $this->redirectToRoute('place_edit');
        }

        $mensaje = 'Imagen añadida con éxito.';
        $this->addFlash('success', $mensaje);
        $appInfoLogger->info($mensaje);

        return $this->render('');
    }
   
   
    /* #[Route('/pelicula/removeactor/{pelicula<\d+>}/{actor<\d+>}', name: 'pelicula_remove_actor')]
    public function removeActor(Pelicula $pelicula,
                            Actor $actor,
                            EntityManagerInterface $em,
                            LoggerInterface $appInfoLogger):Response 
    {

        $this->denyAccessUnlessGranted('edit', $pelicula);

        $pelicula->removeActore($actor);
        $em->flush();

        $mensaje = 'Actor '.$actor->getNombre();
        $mensaje .= ' eliminado de '.$pelicula->getTitulo().' correctamente.';
        $this->addFlash('success', $mensaje);
        $appInfoLogger->info($mensaje);

        return $this->redirectToRoute('pelicula_edit', ['id' => $pelicula->getId()]);
    } */



}
