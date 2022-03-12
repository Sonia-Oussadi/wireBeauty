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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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

        if ($form->isSubmitted()) {
            /* TEMP */
            if ($form->get('file')) {
                $file = $form->get('file')->getData();
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathName());

                /* Legends */

                $tabLegends = $spreadsheet->getSheetByName('légende');
                $highestColumn = $tabLegends->getHighestColumn(); // e.g 'F'

                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

                $products = [];

                for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                    $value = $tabLegends->getCellByColumnAndRow($col, 1)->getValue();
                    switch ($value) {

                        case 'value_product_code':

                            $row = 2;

                            for ($row; $row < 4; $row++) {
                                dump($row);
                                dump($tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue());
                                $products[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            dump($products);

                            break;
                        case 'score_skinbiosense':

                            $row = 2;

                            for ($row; $row < 4; $row++) {
                                dump($row);
                                dump($tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue());
                                $score_skinbiosense[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            dump($score_skinbiosense);

                            break;

                        case 'score_skinbosense':

                            $row = 2;

                            for ($row; $row < 4; $row++) {
                                dump($row);
                                dump($tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue());
                                $score_skinbiosense[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            dump($score_skinbiosense);

                            break;
                    }

                    dump($tabLegends->getHighestColumn());

                    dd('end');
                }
                /* TEMP */
            }


           
        }

        if ($form->isSubmitted() && $form->isValid()) {
            dump("form valid");
            $studyRepository->add($study);
            return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('admin/new_study.html.twig', [
            'study' => $study,
            'form' => $form,
        ]);
    }
}
