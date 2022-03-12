<?php

namespace App\Controller\blog;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
 
      
    #[Route('/articles', name: 'blog_articles')]
    public function  index(): Response
    {
    
        return  new Response("ce n'est pas encore fait");
    }


}
