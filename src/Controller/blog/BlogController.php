<?php

namespace App\Controller\blog;

use App\Repository\ArticleRepository;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class BlogController extends AbstractController
{
 
      
    #[Route('/articles', name: 'blog_articles')]
    public function  index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
    
        return  $this->render('blog/articles.html.twig',[
            'articles'=>$articles
        ]);
    }

    #[Route('/article/{id}', name: 'blog_article')]
    public function  show(ArticleRepository $articleRepository,$id,Request $request): Response
    {
        $article = $articleRepository->findById($id);
  
        if(!empty($request->request)){
            $comment = new Comment();
            $comment->setContent($request->request->get('comment'));
            $article->addComment($comment);
        }
       
        return $this->render('blog/article.html.twig',[
            'article'=>$article
        ]);
    }


}
