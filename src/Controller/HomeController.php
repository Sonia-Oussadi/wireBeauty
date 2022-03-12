<?php

namespace App\Controller;

use App\Repository\StudyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/newNomadikDeviceApp', name: 'app_newNomadikDeviceApp')]
    public function newNomadikDeviceApp(): Response
    {
        return $this->render('home/newNomadikDeviceApp.html.twig');
    }

    #[Route('/services', name: 'app_services')]
    public function us(): Response
    {
        return $this->render('home/services.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    #[Route('/shop', name: 'app_shop')]
    public function shop(StudyRepository $studyRepository): Response
    {
        return $this->render('home/shop.html.twig',[
            'studies' => $studyRepository->findAll()
        ]);
    }


}
