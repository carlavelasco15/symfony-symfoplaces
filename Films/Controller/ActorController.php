<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Actor;
use App\Form\ActorAddPeliculaFormType;
use App\Form\ActorFormType;
use App\Form\ActorDeleteFormType;
use App\Form\SearchFormType;
use App\Service\FileService;
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Pelicula;

class ActorController extends AbstractController
{
   
    #[Route('/actores/{pagina}', defaults: ['pagina' => 1], name: 'actor_list')]
    public function list(int $pagina=1, PaginatorService $paginator):Response
    {

        $paginator->setEntityType('App\Entity\Actor');
        $actores = $paginator->findAllEntities($pagina);
        
        return $this->render('actor/list.html.twig', [
            'actores' => $actores,
            'paginator' => $paginator
        ]);
    }


    #[Route('/actor/search', name: 'actor_search', methods: ['GET', 'POST'])]
    public function search(Request $request, SimpleSearchService $busqueda): Response
    {
        
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'Nombre' => 'nombre',
                'Nacionalidad' => 'nacionalidad',
                'Biografia' => 'biografia',
            ],
            'order_choices' => [
                'ID' => 'id',
                'Nombre' => 'nombre',
                'Nacionalidad' => 'nacionalidad',
                'Biografia' => 'biografia',
            ]
            ]);

        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);

        $formulario->handleRequest($request);

        $actores = $busqueda->search('App\Entity\Actor');

        return $this->renderForm("Actor/buscar.html.twig", [
            "formulario" => $formulario,
            "actores" => $actores
        ]);
    }
    

    #[Route('/actor/store', name: 'actor_create')]
    public function store(
        Request $request,
        FileService $fileService
        ):Response
    {
        
        $entityManager = $this->getDoctrine()->getManager();
        
        $actor = new Actor();
        $this->denyAccessUnlessGranted('create', $actor);

        $formulario = $this->createForm(ActorFormType::class, $actor);
        $formulario->handleRequest($request);
        
        if($formulario->isSubmitted() && $formulario->isValid()) {

            if($uploadedFile = $formulario->get('image')->getData()) {
                $fileService->setTargetDirectory($this->getParameter('app.actors.root'));
                $actor->setImage($fileService->upload($uploadedFile, true, 'actor_'));
            }
            
            $actor->setUser($this->getUser());

            $entityManager = $this->getDoctrine()->getManager();          
            $entityManager->persist($actor);
            $entityManager->flush();
            
            $this->addFlash('success', 'Actor guardado con id ' . $actor->getId());
            
            return $this->redirectToRoute('actor_show', ['id' => $actor->getId()]);
        }
                 
         return $this->render('actor/create.html.twig',
             ['formulario' => $formulario->createView()]);
    }

    #[Route('/actor/borrarFoto/{id}', name: 'actor_delete_image')]
    public function deleteImage(
        Request $request,
        FileService $fileService,
        Actor $actor
    ):Response {

        $this->denyAccessUnlessGranted('edit', $actor);
        
        if($image = $actor->getImage()) {
            $fileService->setTargetDirectory($this->getParameter('app.actors.root'))
                ->remove($image);

            $actor->setImage(NULL);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($actor);
            $entityManager->flush();

            $this->addFlash('success', 'La imagen de ' . $actor->getNombre() . ' fue elminada.');
        }

            return $this->redirecttoRoute('actor_edit', [
                'id' => $actor->getId()
            ]);
    }
    

    
    #[Route('/actor/show/{id<\d+>}', name: 'actor_show')]
    public function show($id):Response {
        $actor = $this->getDoctrine()->getRepository(Actor::class)->find($id);
        
        if(!$actor)
            throw $this->createNotFoundException("No se encontró el actor $id");
        
            return $this->render('actor/detail.html.twig', ['actor' => $actor]);
    }
       
   
    #[Route('/actor/edit/{id}', name: 'actor_edit')]
    public function edit(
        Actor $actor, 
        Request $request,
        FileService $fileService
        ):Response{

        $this->denyAccessUnlessGranted('edit', $actor);

        $formulario = $this->createForm(ActorFormType::class, $actor);
        $imagenAntigua = $actor->getImage();
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario ->isValid()) {

            if($uploadedFile = $formulario->get('image')->getData()) {
                $fileService->setTargetDirectory($this->getParameter('app.actors.root'));

                $actor->setImage($fileService->replace (
                    $uploadedFile,
                    $imagenAntigua,
                    true,
                    'actor_'
                ));
            } else {
                $actor->setImage($imagenAntigua);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Actor actualizado correctamente.');

            return $this->redirectToRoute('actor_show', ['id'=> $actor->getId()]);
        }

        $formularioAddPelicula = $this->createForm(ActorAddPeliculaFormType::class, NULL, [
            'action' => $this->generateUrl('actor_add_pelicula', ['id' => $actor->getId()])
        ]);

        return $this->render("actor/edit.html.twig", [
            "formulario" => $formulario->createView(),
            "actor" => $actor,
            "formularioAddPelicula" => $formularioAddPelicula->createView()
        ]);
    }

    #[Route('/actor/addpelicula/{id<\d+>}', name: 'actor_add_pelicula')]
    public function addPelicula(
            Actor $actor,
            Request $request,
            EntityManagerInterface $em,
            LoggerInterface $appInfoLogger):Response 
        {
            $this->denyAccessUnlessGranted('edit', $actor);

            $formularioAddActor = $this->createForm(ActorAddPeliculaFormType::class);
            $formularioAddActor->handleRequest($request);
            $pelicula = $formularioAddActor->getData()['pelicula'];
            $actor->addPelicula($pelicula);
            $em->flush();

            $mensaje = 'Pelicula ' .$pelicula->getTitulo();
            $mensaje .= ' añadido a ' .$actor->getNombre(). ' correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje);

            return $this->redirectToRoute('actor_edit', ['id' => $actor->getId()]);


        }


    #[Route('/actor/removepelicula/{pelicula<\d+>}/{actor<\d+>}', name: 'actor_remove_pelicula')]
    public function removePelicula(
            Actor $actor,
            Pelicula $pelicula,
            EntityManagerInterface $em,
            LoggerInterface $appInfoLogger):Response 
        {
            $this->denyAccessUnlessGranted('edit', $actor);

            $actor->removePelicula($pelicula);
            $em->flush();

            $mensaje = 'Pelicula ' .$pelicula->getTitulo();
            $mensaje .= ' eliminado de ' .$actor->getNombre(). ' correctamente.';
            $this->addFlash('success', $mensaje);
            $appInfoLogger->info($mensaje);

            return $this->redirectToRoute('actor_edit', ['id' => $actor->getId()]);


        }
    
    
    #[Route('/actor/delete/{id}', name: 'actor_delete')]
    public function delete(
        Actor $actor, 
        Request $request,
        FileService $fileService): Response{

        $this->denyAccessUnlessGranted('edit', $actor);

        $formulario = $this->createForm(ActorDeleteFormType::class, $actor);
        $formulario->handleRequest($request);

        if($formulario->isSubmitted() && $formulario->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($actor);
            $entityManager->flush();

            if($image = $actor->getImage()) {
                $fileService->setTargetDirectory($this->getParameter('app.actors.root'))
                            ->remove($image);
            }

            
            $this->addFlash('success', 'Actor eliminado correctamente.');

            return $this->redirectToRoute('actor_list');
        }

        return $this->render("actor/delete.html.twig", [
            "formulario" => $formulario->createView(),
            "actor" => $actor
        ]);
    }
    
    
}
