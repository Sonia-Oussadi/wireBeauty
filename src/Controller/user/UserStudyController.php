<?php

namespace App\Controller\user;

use App\Repository\BillRepository;
use App\Repository\StudyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserStudyController extends AbstractController 
{
    
    #[Route('/study', name: 'user_study')]
    public function study(StudyRepository $studyRepository,UserRepository $userRepository): Response
    {
        $user = $this->getUser()->getUserIdentifier();
        $studies  = $studyRepository->findUserStudies($this->getUser());
        return $this->render('user/studies/listStudies.html.twig',[
            'studies' =>$studies
        ]);
    }

    #[Route('/bills', name: 'user_bill')]
    public function bills(BillRepository $billRepository): Response
    {
        $bills = $billRepository->findUserBills($this->getUser());
        return $this->render('user/studies/listBills.html.twig',[
            'bills' =>$bills
        ]);
    }

}
