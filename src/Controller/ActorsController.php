<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Form\ActorFormType;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActorsController extends AbstractController
{
    private $em;
    private $actorRepository;

    public function __construct(ActorRepository $actorRepository,EntityManagerInterface $em)
    {
        $this->actorRepository = $actorRepository;
        $this->em = $em;
    }


    #[Route('/actors', name: 'app_actors')]
    public function index(): Response
    {
        $actors = $this->actorRepository->findAll();
        return $this->render('actors/index.html.twig', [
            'actors' => $actors
        ]);
    }


    #[Route('/actors/create', name: 'actor_create')]
    public function create(Request $request): Response
    {
        $actor = new Actor();
        $form = $this->createForm(ActorFormType::class,$actor);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($actor);
            $this->em->flush();

            return $this->redirectToRoute('app_actors');
        }

        return $this->render('actors/create.html.twig', [
            'form' => $form->createView(), // Correction : passer le formulaire à Twig
        ]);
    }

    #[Route('/actors/edit/{id}', name: 'actor_edit')]
    public function edit($id,Request $request): Response
    {
        $actor = $this->actorRepository->find($id);
        $form = $this->createForm(ActorFormType::class, $actor);
        $form->handleRequest($request);

        if($form->isSubmitted()){
            $actor->setName($form->get('name')->getData());

            $this->em->flush();
            return $this->redirectToRoute('app_actors');            
        }
        return $this->render('actors/edit.html.twig',[
            'actor' => $actor,
            'form' => $form->createView()
        ]);

    }

    #[Route('/actors/delete/{id}',methods: ['GET','DELETE'] ,name:'delete_actor')]
    public function delete($id):Response
    {
        $actor = $this->actorRepository->find($id);
        if($actor){
            $this->em->remove($actor);
            $this->em->flush();        
        }
        return $this->redirectToRoute('app_actors');
    }
}
