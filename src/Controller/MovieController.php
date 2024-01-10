<?php

namespace App\Controller;

use App\Entity\Movie as MovieEntity;
use App\Form\MovieType;
use App\Model\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class MovieController extends AbstractController
{
    #[Route('/movies', name: 'app_movies_list', methods: ['GET'])]
    public function list(MovieRepository $movieRepository): Response
    {
        return $this->render('movie/list.html.twig', [
            'movies' => Movie::fromEntities($movieRepository->listAll()),
        ]);
    }

    #[Route(
        path: '/movies/{slug}',
        requirements: [
            'slug' => '\d{4}-'.Requirement::ASCII_SLUG,
        ],
        name: 'app_movies_details',
        methods: ['GET'],
    )]
    public function details(MovieRepository $movieRepository, string $slug): Response
    {
        return $this->render('movie/details.html.twig', [
            'movie' => Movie::fromEntity($movieRepository->getBySlug($slug)),
        ]);
    }

    #[Route(
        path: '/movies/new',
        name: 'app_movies_new',
        methods: ['GET', 'POST'],
    )]
    #[Route(
        '/movies/{slug}/edit',
        name: 'app_movies_edit',
        requirements: [
            'slug' => '\d{4}-'.Requirement::ASCII_SLUG
        ],
        methods: ['GET', 'POST']
    )]
    public function newOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        MovieRepository $movieRepository,
        string|null $slug = null,
    ): Response {
        $movieEntity = new MovieEntity();
        if (null !== $slug) {
            $movieEntity = $movieRepository->getBySlug($slug);
        }

        $movieForm = $this->createForm(MovieType::class, $movieEntity);
        $movieForm->handleRequest($request);

        if ($movieForm->isSubmitted() && $movieForm->isValid()) {
            $entityManager->persist($movieEntity);
            $entityManager->flush();

            return $this->redirectToRoute('app_movies_details', ['slug' => $movieEntity->getSlug()]);
        }

        return $this->render('movie/new_or_edit.html.twig', [
            'movie_form' => $movieForm->createView(),
            'editing_movie' => null !== $slug ? Movie::fromEntity($movieEntity) : null,
        ]);
    }
}
