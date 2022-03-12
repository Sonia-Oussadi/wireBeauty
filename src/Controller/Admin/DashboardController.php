<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Study;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\TextUI\XmlConfiguration\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpParser\Node\Expr\Yield_;
use Symfony\Bundle\MakerBundle\Security\UserClassBuilder;

#[Route('/admin')]
class DashboardController extends AbstractDashboardController
{

    #[Route('/', 'admin')]
    public function index(): Response
    {

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        //return parent::index();
        // return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    #[Route('/studies', name: 'dashboard_studies')]
    public function showStudies() : Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(StudyCrudController::class)->generateUrl());
    }

    #[Route('/users', name: 'dashboard_users')]
    public function showUsers(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }
    
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Wired Beauty')
             ->setFaviconPath('images/favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        return [
            
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

            MenuItem::section('Study'),
                MenuItem::linkToCrud('Studies', 'fa fa-solid fa-file', Study::class),
                MenuItem::linkToRoute('Generate Study', 'fa fa-solid fa-gear', 'app_new_study'),

            MenuItem::section(),
            MenuItem::linkToCrud('Users', 'fa fa-solid fa-users',User::class),
                MenuItem::linkToRoute('Account Settings','fa fa-solid fa-gear','user_profil'),
            MenuItem::section(),
            MenuItem::section(),

                MenuItem::linkToRoute('Wired Beauty Website', 'fa fa-tablet','app_home'),
            MenuItem::section(),
                MenuItem::linkToCrud('Articles','',Article::class),
                MenuItem::linkToCrud('categories','',Category::class)

        ];
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
