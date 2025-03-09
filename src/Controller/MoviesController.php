<?php

namespace App\Controller;

use App\Form\MovieFormType;
use App\Repository\MovieRepository;
use App\Repository\ActorRepository;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Json;

class MoviesController extends AbstractController
{    
    private $em;
    private $movieRepository;
    private $actorRepository;
    public function __construct(MovieRepository $movieRepository,ActorRepository $actorRepository,EntityManagerInterface $em)
    {
        $this->movieRepository = $movieRepository;
        $this->actorRepository = $actorRepository;
        $this->em = $em;
    }

    #[Route('/movies', methods:['GET'] , name:'movies')]
    public function index():Response
    {
        $movies = $this->movieRepository->findAll();
        return $this->render('movies/index.html.twig',[
            'movies' => $movies
        ]);
    }

    #[Route('/api/movies', methods:['GET'] , name:'api_movies')]
    public function index2(Request $request):Response
    {
        // Récupérer les paramètres de la requête
        $page = $request->query->getInt('page', 1); // Par défaut, la page 1
        $limit = $request->query->getInt('limit', 2); // Par défaut, 2 films par page

        // Calculer l'offset
        $offset = ($page - 1) * $limit;
        $movies = $this->movieRepository->findBy([], null, $limit, $offset);

        $moviesDTO = array_map(fn($movie) => [
            'id' => $movie->getId(),
            'title' => $movie->getTitle(), 
            'realiseyear' => $movie->getReleaseYear(),
            'description' => $movie->getDescription(),
            'imagePath' => $movie->getImagePath()
        ], $movies);
        // Retourner la réponse JSON
        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => count($this->movieRepository->findAll()), // Nombre total de films
            'movies' => $moviesDTO
        ]);
    }

    #[Route('/movies/create' , name:'create_movie')]
    public function create(Request $request): Response
    {
        if(!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('error','Vous n\'avez pas l\'autorisation d\'accéder à cette page.');
            return $this->redirectToRoute('movies');
        }

        $movie = new Movie();
        $form = $this->createForm(MovieFormType::class,$movie);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $newMovie = $form->getData();
            $imagePath = $form->get('imagePath')->getData();
            // dd($imagePath);
            if($imagePath instanceof UploadedFile){
                // dd($imagePath->guessExtension());
                $newFileName = uniqid() . '.' . $imagePath->guessExtension();
                
                try{
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir') . '/public/images',
                        $newFileName
                    );
                } catch(FileException $e){
                    return new Response($e->getMessage());
                }

                $newMovie->setImagePath('/images/'. $newFileName);
            }
            $this->em->persist($newMovie);
            $this->em->flush();
            
            return $this->redirectToRoute('movies');
        }

        return $this->render('movies/create.html.twig',[
            'form' => $form->createView()
        ]);
    }

    #[Route('/api/movies' , methods:['POST'] , name:'api_create_movie')]
    public function create2(Request $request): Response
    {
        $movie = new Movie();
        $movie->setTitle($request->request->get('title'));
        $movie->setReleaseYear($request->request->get('releaseyear'));
        $movie->setDescription($request->request->get('description'));
        $imagePath = $request->files->get('imagePath');
        if($imagePath instanceof UploadedFile){
            $newFileName = uniqid() . '.' . $imagePath->guessExtension();
            try{
                $imagePath->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $newFileName
                );
            } catch(FileException $e){
                return new Response($e->getMessage());
            }
            $movie->setImagePath('/images/'. $newFileName);
        }

        $this->em->persist($movie);
        $this->em->flush();

        return new Response('Movie created successfully', Response::HTTP_CREATED);
    }
   
    #[Route('/movies/edit/{id}', name:'edit_movie')]
    public function edit($id, Request $request):Response
    {
        if(!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('error','Vous n\'avez pas l\'autorisation d\'accéder à cette page.');
            return $this->redirectToRoute('movies');
        }

        $movie = $this->movieRepository->find($id);
        $form = $this->createForm(MovieFormType::class, $movie);

        $form->handleRequest($request);
        $imagePath = $form->get('imagePath')->getData();
        

        if($form->isSubmitted() && $form->isValid()){
            if($imagePath){
                $newFileName = uniqid() . '.' . $imagePath->guessExtension(); 
                try{
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir') . '/public/images',
                        $newFileName
                    );
                } catch(FileException $e){
                    return new Response($e->getMessage());
                }

                $movie->setImagePath('/images/'. $newFileName);
            }                        
            $movie->setTitle($form->get('title')->getData());
            $movie->setReleaseYear($form->get('releaseYear')->getData());
            $movie->setDescription($form->get('description')->getData());

            $this->em->flush();
            return $this->redirectToRoute('movies');
        }
        
        return $this->render('movies/edit.html.twig',[
            'movie' => $movie,
            'form' => $form->createView()
        ]);
    }

    #[Route('/api/movies/{id}', methods:['PATCH','PUT'] , name:'api_edit_movie')]
    public function edit2($id, Request $request): JsonResponse
    {
        // Vérifier si l'ID est bien reçu
        if (!$id) {
            return new JsonResponse(['error' => 'ID is missing'], 400);
        }

        $movie = $this->em->getRepository(Movie::class)->find($id);
        
        // Vérifier si le film existe
        if (!$movie) {
            return new JsonResponse(['error' => 'Movie not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        // Mise à jour des champs s'ils existent dans la requête
        if (isset($data['title'])) {
            $movie->setTitle($data['title']);
        }
        if (isset($data['releaseyear'])) {
            $movie->setReleaseYear($data['releaseyear']);
        }
        if (isset($data['description'])) {
            $movie->setDescription($data['description']);
        }
        $this->em->persist($movie);   
        $this->em->flush();

        // Vérifier si les données ont bien été modifiées
        return new JsonResponse([
            'message' => 'Movie updated successfully',
            'movie' => [
                'id' => $movie->getId(),
                'title' => $movie->getTitle(),
                'releaseyear' => $movie->getReleaseYear(),
                'description' => $movie->getDescription(),
            ]
        ], 200);
    }

    #[Route('/api/movies/editimg/{id}' , methods:['POST'] , name:'api_edit_img_movie')]
    public function editImg($id,Request $request): Response
    {
        // Vérifier si l'ID est bien reçu
        if (!$id) {
            return new JsonResponse(['error' => 'ID is missing'], 400);
        }
        $movie = $this->em->getRepository(Movie::class)->find($id);  
        // Vérifier si le film existe
        if (!$movie) {
            return new JsonResponse(['error' => 'Movie not found'], 404);
        }

        $imagePath = $request->files->get('imagePath');
        // Vérification si une image a bien été envoyée

        if (!$imagePath) {
            return new JsonResponse(['error' => 'No image uploaded'], 400);
        }

        if($imagePath instanceof UploadedFile){

            $newFileName = uniqid() . '.' . $imagePath->guessExtension();
            
            try{
                $imagePath->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $newFileName
                );
            } catch(FileException $e){
                return new Response($e->getMessage());
            }

            $movie->setImagePath('/images/'. $newFileName);

            $this->em->persist($movie);
            $this->em->flush();
            
            return new JsonResponse([
                'message' => 'Image updated successfully',
                'imagePath' => $movie->getImagePath()
            ], 200);

        }
        return new JsonResponse(['error' => 'Invalid file'], 400);
    }

    #[Route('/movies/delete/{id}',methods: ['GET','DELETE'] ,name:'delete_movie')]
    public function delete($id):Response
    {
        if(!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('error','Vous n\'avez pas l\'autorisation d\'accéder à cette page.');
            return $this->redirectToRoute('movies');
        }

        $movie = $this->movieRepository->find($id);
        $this->em->remove($movie);
        $this->em->flush();

        return $this->redirectToRoute('movies');
    }

    #[Route('/api/movies/{id}',methods: ['GET','DELETE'] ,name:'api_delete_movie')]
    public function delete2($id)
    {
        $movie = $this->em->getRepository(Movie::class)->find($id);

        $this->em->remove($movie);
        $this->em->flush();
    }

    #[Route('/movies/{id}', methods:['GET'] , name:'show_movie')]
    public function show($id):Response
    {
        $movie = $this->movieRepository->find($id);
        return $this->render('movies/show.html.twig',[
            'movie' => $movie
        ]);
    }

    #[Route('movies/remove-actor/{movieId}/{actorId}',name:'remove_actor')]
    public function removeActor(int $movieId,int $actorId): Response
    {
        $movie = $this->movieRepository->find($movieId);
        $actor = $this->actorRepository->find($actorId);

        $movie->removeActor($actor);

        // Sauvegarde en base de données
        $this->em->flush();
        // Redirection vers la page du film
        return $this->redirectToRoute('show_movie', ['id' => $movieId]);
    }
}
