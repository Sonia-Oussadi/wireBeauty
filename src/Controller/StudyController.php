<?php

namespace App\Controller;

use App\Entity\Study;
use App\Form\StudyType;
use App\Repository\StudyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

#[Route('/study')]

class StudyController extends AbstractController
{
    #[Route('/', name: 'app_study')]
    public function index(): Response
    {
        return $this->render('study/index.html.twig', [
            'controller_name' => 'StudyController',
        ]);
    }

    #[Route('/view/{id}', name: 'app_view_study', methods: ['GET'])]
    public function show(Study $study): Response
    {
        $path = $this->getParameter('publicDir') . '/' .   $study->getPath();
        return $this->file($path, $study->getName(), ResponseHeaderBag::DISPOSITION_INLINE);
    }


    #[Route('/download/{id}', name: 'app_download_study', methods: ['GET'])]
    public function download(Study $study): Response
    {
        $path = $this->getParameter('publicDir') . '/' .   $study->getPath();
        return $this->file($path, $study->getName() . '.pdf');
    }

    #[Route('/{id}/edit', name: 'app_edit_study', methods: ['GET', 'POST'])]
    public function edit(Request $request, Study $study, StudyRepository $studyRepository): Response
    {
        $form = $this->createForm(StudyType::class, $study);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $studyRepository->add($study);
            return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/edit_study/edit.html.twig', [
            'study' => $study,
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'app_new_study', methods: ['GET', 'POST'])]
    public function new(Request $request, StudyRepository $studyRepository): Response
    {
        $study = new Study();
        $form = $this->createForm(StudyType::class, $study);
        $form->handleRequest($request);

        /* TEMP */
        if ($form->get('file')) {
            $file = $form->get('file')->getData();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathName());

            /* Hydratation */

            $tabLegends = $spreadsheet->getSheetByName('légende');


            dd('end');
        }
        /* TEMP */

        if ($form->isSubmitted() && $form->isValid()) {
            dump("form valid");
            $studyRepository->add($study);
            return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);

        }elseif($form->isSubmitted() ) {

            dump("not good");
            dd($request);
        }

        return $this->renderForm('admin/new_study.html.twig', [
            'study' => $study,
            'form' => $form,
        ]);
    }
}
