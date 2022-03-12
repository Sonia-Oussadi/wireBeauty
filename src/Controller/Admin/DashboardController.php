<?php

namespace App\Controller\Admin;

use App\Entity\Study;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\TextUI\XmlConfiguration\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
#[Route('/admin')]
class DashboardController extends AbstractDashboardController
{

    #[Route('/')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);

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
                MenuItem::linkToCrud('Studies', 'fa fa-tags', Study::class),
                MenuItem::linkToRoute('Generate Study', 'fa fa-tags', 'app_new_study')
        ];

        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
