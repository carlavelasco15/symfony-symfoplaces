<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Pelicula;
use App\Entity\Actor;
use App\Form\PeliculaFormType;
use App\Form\PeliculaDeleteFormType;
use App\Form\SearchFormType;
use App\Form\PeliculaAddActorFormType;
use Psr\Log\LoggerInterface;
use App\Service\FileService;
use App\Repository\PeliculaRepository;
use App\Service\PaginatorService;
use App\Service\SimpleSearchService;
use Doctrine\ORM\EntityManagerInterface;


class PeliculaController extends AbstractController
{

    #[Route('/peliculas/{pagina}', defaults: ['pagina' => 1],name: 'pelicula_list', methods: ['GET'])]
    public function list(int $pagina=1, PaginatorService $paginator):Response
    {
        $paginator->setEntityType('App\Entity\Pelicula');
        $peliculas = $paginator->findAllEntities($pagina);
        
        return $this->render('pelicula/list.html.twig', [
            'peliculas' => $peliculas,
            'paginator' => $paginator
        ]);
    }
    

    #[Route('/pelicula/search', name: 'pelicula_search', methods: ['GET', 'POST'])]
    public function search(Request $request, SimpleSearchService $busqueda): Response
    {
        
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'Título' => 'titulo',
                'Director' => 'director',
                'Género' => 'genero',
                'Sinopsis' => 'sinopsis'
            ],
            'order_choices' => [
                'ID' => 'id',
                'Título' => 'titulo',
                'Director' => 'director',
                'Género' => 'genero',
            ]
            ]);

        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);

        $formulario->handleRequest($request);

        $pelis = $busqueda->search('App\Entity\Pelicula');

        return $this->renderForm("pelicula/buscar.html.twig", [
            "formulario" => $formulario,
            "peliculas" => $pelis
        ]);
    }

    

    #[Route('/pelicula/store', name: 'pelicula_create', methods: ["GET", "POST"])]
    public function store(
        Request $request, 
        LoggerInterface $appInfoLogger,
        FileService $fileService):Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $peli = new Pelicula();
        $this->denyAccessUnlessGranted('create', $peli);

        $formulario = $this->createForm(PeliculaFormType::class, $peli);
        $formulario->handleRequest($request);

        if($formulario->isSubmitted() && $formulario->isValid()) {

            if($uploadedFile = $formulario->get('image')->getData()) {
                $fileService->setTargetDirectory($this->getParameter('app.covers.root'));
                $peli->setCaratula($fileService->upload($uploadedFile, true, 'cover_'));
            }

            $peli->setUser($this->getUser());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($peli);
            $entityManager->flush();
            
            $mensaje = 'Película ' . $peli->getTitulo(). ' guardada con id ' . $peli->getId();
            $this->addFlash('success', 'Película guardada con id ' . $peli->getId());
            $appInfoLogger->warning($mensaje);
            
            return $this->redirectToRoute('pelicula_show', ['id' => $peli->getId()]);
        }
                 
         return $this->render('pelicula/create.html.twig',
             ['formulario' => $formulario->createView()]);
    }



    #[Route('/pelicula/borrarCaratula/{id}', name: 'pelicula_delete_cover')]
    public function deleteCover(
        Request $request,
        FileService $fileService,
        Pelicula $pelicula
    ):Response {

        $this->denyAccessUnlessGranted('edit', $pelicula);

        
        if($caratula = $pelicula->getCaratula()) {
            $fileService->setTargetDirectory($this->getParameter('app.covers.root'))
                        ->remove($caratula);

            $pelicula->setCaratula(NULL);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($pelicula);
            $entityManager->flush();

            $this->addFlash('success', 'La carátula de ' . $pelicula->getTitulo() . ' fue borrada.');
        }
        
            return $this->redirectToRoute('pelicula_edit', [
                'id' => $pelicula->getId()
                ]);
    }
    
  

    #[Route('/pelicula/show/{id<\d+>}', name: 'pelicula_show')]
    public function show($id):Response {
        $peli = $this->getDoctrine()->getRepository(Pelicula::class)->find($id);
        
        if(!$peli)
            throw $this->createNotFoundException("No se encontró la peli $id");
        
            return $this->render('pelicula/detail.html.twig', ['pelicula' => $peli]);
    }
   


    #[Route('/pelicula/addactor/{id<\d+>}', name: 'pelicula_add_actor')]
    public function addActor(Pelicula $pelicula,
                            Request $request,
                            EntityManagerInterface $em,
                            LoggerInterface $appInfoLogger):Response 
    {
        $this->denyAccessUnlessGranted('edit', $pelicula);

        $formularioAddActor = $this->createForm(PeliculaAddActorFormType::class);
        $formularioAddActor->handleRequest($request);
        $actor = $formularioAddActor->getData()['actor'];
        $pelicula->addActore($actor);
        $em->flush();

        $mensaje = 'Actor '.$actor->getNombre();
        $mensaje .= ' añadido a ' .$pelicula->getTitulo(). ' correctamente.';
        $this->addFlash('success', $mensaje);
        $appInfoLogger->info($mensaje);

        return $this->redirectToRoute('pelicula_edit', ['id' => $pelicula->getId()]);
    }
   
   
    #[Route('/pelicula/removeactor/{pelicula<\d+>}/{actor<\d+>}', name: 'pelicula_remove_actor')]
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
    }
        

    
    #[Route('/pelicula/edit/{id}', name: 'pelicula_edit')]
    public function edit(
            Pelicula $peli, 
            Request $request,
            FileService $fileService
        ):Response{

        $this->denyAccessUnlessGranted('edit', $peli);

        $formulario = $this->createForm(PeliculaFormType::class, $peli);
        $caratulaAntigua = $peli->getCaratula();
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario ->isValid()) {

            if($uploadedFile = $formulario->get('image')->getData()) {

            $fileService->setTargetDirectory($this->getParameter('app.covers.root'));

            $peli->setCaratula($fileService->replace (
                $uploadedFile,
                $caratulaAntigua,
                true,
                'cover_'
            ));
        } else {
            $peli->setCaratula($caratulaAntigua);
        }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Película actualizada correctamente.');

            return $this->redirectToRoute('pelicula_show', ['id'=> $peli->getId()]);
        }

        $formularioAddActor = $this->createForm(PeliculaAddActorFormType::class, NULL, [
            'action' => $this->generateUrl('pelicula_add_actor', ['id' =>$peli->getId()])
        ]);


        return $this->render("pelicula/edit.html.twig", [
            "formulario" => $formulario->createView(),
            "pelicula" => $peli,
            "formularioAddActor" => $formularioAddActor->createView()
        ]);
    }
    


    #[Route('/pelicula/delete/{id}', name: 'pelicula_delete')]
    public function delete(
        Pelicula $peli, 
        Request $request,
        FileService $fileService): Response{

        $this->denyAccessUnlessGranted('edit', $peli);


        $formulario = $this->createForm(PeliculaDeleteFormType::class, $peli);
        $formulario->handleRequest($request);

        if($formulario->isSubmitted() && $formulario->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($peli);
            $entityManager->flush();
            
            if($caratula = $peli->getCaratula()) {
                $fileService->setTargetDirectory($this->getParameter('app.covers.root'))
                            ->remove($caratula);
            }

            $this->addFlash('success', 'Película eliminada correctamente.');

            return $this->redirectToRoute('pelicula_list');
        }

        return $this->render("pelicula/delete.html.twig", [
            "formulario" => $formulario->createView(),
            "pelicula" => $peli
        ]);
    }
    
    
}
    
