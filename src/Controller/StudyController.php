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
use App\Form\AddToCartType;
use App\Manager\CartManager;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Dompdf\Dompdf;
use Dompdf\Options;
use QuickChart;

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

    /**
     * @Route("/shop/{id}", name="shop.detail")
     */
    public function shop(Study $study, Request $request, CartManager $cartManager): Response
    {
        $form = $this->createForm(AddToCartType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();
            $item->setProduct($study);

            $cart = $cartManager->getCurrentCart();
            $cart
                ->addItem($item)
                ->setUpdatedAt(new \DateTimeImmutable());

            $cartManager->save($cart);

            return $this->redirectToRoute('shop.detail', ['id' => $study->getId()]);
        }

        return $this->render('product/detail.html.twig', [
            'study' => $study,
            'form' => $form->createView(),
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
    public function new(Request $request, StudyRepository $studyRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $study = new Study();
        $form = $this->createForm(StudyType::class, $study);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if (!empty($form->get('file'))) {

                $file = $form->get('file')->getData();
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathName());

                /* GET LEGENDS */

                $tabLegends = $spreadsheet->getSheetByName('légende');
                $highestColumn = $tabLegends->getHighestColumn(); // e.g 'F'
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

                $products = [];
                $questions = [];
                $score_skinbiosense = [];
                $zone_codes = [];
                $session_ids = [];

                for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                    $cell = $tabLegends->getCellByColumnAndRow($col, 1);
                    $value = $tabLegends->getCellByColumnAndRow($col, 1)->getValue();
                    $row = 2;
                    $maxRow = $tabLegends->getHighestRow($cell->getColumn());

                    switch ($value) {

                        case 'value_product_code':

                            for ($row; $row <= $maxRow; $row++) {
                                $products[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            // dump($products);

                            break;
                        case 'score_skinbiosense':

                            for ($row; $row <= $maxRow; $row++) {
                                $score_skinbiosense[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            // dump($score_skinbiosense);

                            break;

                        case 'zone_code':

                            for ($row; $row <= $maxRow; $row++) {
                                $zone_codes[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            // dump($zone_codes);

                            break;

                        case 'session_id':

                            for ($row; $row <= $maxRow; $row++) {
                                $session_ids[$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            // dump($session_ids);

                            break;

                        case (preg_match('/question_Q(\d)*/', $value) ? true : false):

                            preg_match('/(\d)*$/', $value, $num);

                            for ($row; $row <= $maxRow; $row++) {
                                $questions[(string)$num[0]][$tabLegends->getCellByColumnAndRow($col, $row)->getValue()] = $tabLegends->getCellByColumnAndRow($col + 1, $row)->getValue();
                            }
                            // dump($questions);

                            break;
                    }
                }

                /* GET STATS */
                $tabDatas = $spreadsheet->getSheetByName('score_skinbiosense');
                $highestColumn = $tabDatas->getHighestColumn(); // e.g 'F'
                $highestRowIndex = $tabDatas->getHighestRow("A");
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5
                $menus = []; {
                    /* hydrantant */
                    $hydratantsFirstProductZoneNonTraite[0] = 0;
                    $hydratantsFirstProductZoneNonTraite[1] = 0;
                    $hydratantsFirstProductZoneNonTraite[2] = 0;
                    $hydratantsFirstProductZoneNonTraite[3] = 0;

                    $hydratantsFirstProductZoneTraite[0] = 0;
                    $hydratantsFirstProductZoneTraite[1] = 0;
                    $hydratantsFirstProductZoneTraite[2] = 0;
                    $hydratantsFirstProductZoneTraite[3] = 0;

                    $hydratantsSecondProductZoneNonTraite[0] = 0;
                    $hydratantsSecondProductZoneNonTraite[1] = 0;
                    $hydratantsSecondProductZoneNonTraite[2] = 0;
                    $hydratantsSecondProductZoneNonTraite[3] = 0;

                    $hydratantsSecondProductZoneTraite[0] = 0;
                    $hydratantsSecondProductZoneTraite[1] = 0;
                    $hydratantsSecondProductZoneTraite[2] = 0;
                    $hydratantsSecondProductZoneTraite[3] = 0;

                    /* anti oxydant */

                    $antiOxydantFirstProductZoneNonTraite[0] = 0;
                    $antiOxydantFirstProductZoneNonTraite[1] = 0;
                    $antiOxydantFirstProductZoneNonTraite[2] = 0;
                    $antiOxydantFirstProductZoneNonTraite[3] = 0;

                    $antiOxydantFirstProductZoneTraite[0] = 0;
                    $antiOxydantFirstProductZoneTraite[1] = 0;
                    $antiOxydantFirstProductZoneTraite[2] = 0;
                    $antiOxydantFirstProductZoneTraite[3] = 0;

                    $antiOxydantSecondProductZoneNonTraite[0] = 0;
                    $antiOxydantSecondProductZoneNonTraite[1] = 0;
                    $antiOxydantSecondProductZoneNonTraite[2] = 0;
                    $antiOxydantSecondProductZoneNonTraite[3] = 0;

                    $antiOxydantSecondProductZoneTraite[0] = 0;
                    $antiOxydantSecondProductZoneTraite[1] = 0;
                    $antiOxydantSecondProductZoneTraite[2] = 0;
                    $antiOxydantSecondProductZoneTraite[3] = 0;

                    /* barriere */

                    $barriereFirstProductZoneNonTraite[0] = 0;
                    $barriereFirstProductZoneNonTraite[1] = 0;
                    $barriereFirstProductZoneNonTraite[2] = 0;
                    $barriereFirstProductZoneNonTraite[3] = 0;

                    $barriereFirstProductZoneTraite[0] = 0;
                    $barriereFirstProductZoneTraite[1] = 0;
                    $barriereFirstProductZoneTraite[2] = 0;
                    $barriereFirstProductZoneTraite[3] = 0;

                    $barriereSecondProductZoneNonTraite[0] = 0;
                    $barriereSecondProductZoneNonTraite[1] = 0;
                    $barriereSecondProductZoneNonTraite[2] = 0;
                    $barriereSecondProductZoneNonTraite[3] = 0;

                    $barriereSecondProductZoneTraite[0] = 0;
                    $barriereSecondProductZoneTraite[1] = 0;
                    $barriereSecondProductZoneTraite[2] = 0;
                    $barriereSecondProductZoneTraite[3] = 0;
                }

                for ($col = 1; $col <= $highestColumnIndex; $col++) {

                    $val = $tabDatas->getCellByColumnAndRow($col, 1)->getValue();

                    if ($val !== NULL) {
                        if ($val == 'mesure') $menus['mesure'] = $col;
                        if ($val == 'score_skinbiosense') $menus['score_skinbiosense'] = $col;
                        if ($val == 'session_id') $menus['session_id'] = $col;
                        if ($val == 'zone_code') $menus['zone_code'] = $col;
                        if ($val == 'user_id') $menus['user_id'] = $col;
                        if ($val == 'product_code') $menus['product_code'] = $col;
                    }
                }

                for ($row = 2; $row <= $highestRowIndex; $row++) {

                    $skinbiosense = $tabDatas->getCellByColumnAndRow($menus['score_skinbiosense'], $row)->getValue();
                    $session_id = $tabDatas->getCellByColumnAndRow($menus['session_id'], $row)->getValue();
                    $product_id = $tabDatas->getCellByColumnAndRow($menus['product_code'], $row)->getValue();
                    $mesure = $tabDatas->getCellByColumnAndRow($menus['mesure'], $row)->getValue();
                    $zone_code = $tabDatas->getCellByColumnAndRow($menus['zone_code'], $row)->getValue();

                    // HYDRATANT
                    {
                        // hydratant + Temp + First product + Non traite
                        if ($skinbiosense == 2 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $hydratantsFirstProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $hydratantsFirstProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $hydratantsFirstProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $hydratantsFirstProductZoneNonTraite[3] += $mesure;

                        // hydratant + Temp + First product + traite
                        if ($skinbiosense == 2 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $hydratantsFirstProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $hydratantsFirstProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $hydratantsFirstProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $hydratantsFirstProductZoneTraite[3] += $mesure;

                        // hydratant + Temp + Second product + Non traite
                        if ($skinbiosense == 2 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $hydratantsSecondProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $hydratantsSecondProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $hydratantsSecondProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $hydratantsSecondProductZoneNonTraite[3] += $mesure;

                        // hydratant + Temp + Second product + traite
                        if ($skinbiosense == 2 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $hydratantsSecondProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $hydratantsSecondProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $hydratantsSecondProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 2 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $hydratantsSecondProductZoneTraite[3] += $mesure;
                    }

                    // ANTI OXYDANT
                    {
                        // oxydant + Temp + First product + Non traite
                        if ($skinbiosense == 1 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $antiOxydantFirstProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $antiOxydantFirstProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $antiOxydantFirstProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $antiOxydantFirstProductZoneNonTraite[3] += $mesure;

                        // oxydant + Temp + First product + traite
                        if ($skinbiosense == 1 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $antiOxydantFirstProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $antiOxydantFirstProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $antiOxydantFirstProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $antiOxydantFirstProductZoneTraite[3] += $mesure;

                        // oxydant + Temp + Second product + Non traite
                        if ($skinbiosense == 1 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $antiOxydantSecondProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $antiOxydantSecondProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $antiOxydantSecondProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $antiOxydantSecondProductZoneNonTraite[3] += $mesure;

                        // oxydant + Temp + Second product + traite
                        if ($skinbiosense == 1 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $antiOxydantSecondProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $antiOxydantSecondProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $antiOxydantSecondProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 1 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $antiOxydantSecondProductZoneTraite[3] += $mesure;
                    }

                    // BARRIERE
                    {
                        // barriere + Temp + First product + Non traite
                        if ($skinbiosense == 3 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $barriereFirstProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $barriereFirstProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $barriereFirstProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 1)  $barriereFirstProductZoneNonTraite[3] += $mesure;

                        // barriere + Temp + First product + traite
                        if ($skinbiosense == 3 && $session_id == 1 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $barriereFirstProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 2 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $barriereFirstProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 3 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $barriereFirstProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 4 && $product_id == array_search('SKC', $products, true) && $zone_code == 2)  $barriereFirstProductZoneTraite[3] += $mesure;

                        // barriere + Temp + Second product + Non traite
                        if ($skinbiosense == 3 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $barriereSecondProductZoneNonTraite[0] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $barriereSecondProductZoneNonTraite[1] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $barriereSecondProductZoneNonTraite[2] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 1)  $barriereSecondProductZoneNonTraite[3] += $mesure;

                        // barriere + Temp + Second product + traite
                        if ($skinbiosense == 3 && $session_id == 1 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $barriereSecondProductZoneTraite[0] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 2 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $barriereSecondProductZoneTraite[1] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 3 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $barriereSecondProductZoneTraite[2] += $mesure;
                        if ($skinbiosense == 3 && $session_id == 4 && $product_id == array_search('VITC', $products, true) && $zone_code == 2)  $barriereSecondProductZoneTraite[3] += $mesure;
                    }
                }

                $session_ids = ["Jour 0", "Jour 0 immédiat après application", "Jour 7", "Jour 14"];
                /* BUILD CHARTS */
                $hydratantfirstProduct = $chartBuilder->createChart(Chart::TYPE_LINE);
                $hydratantsecondProduct = $chartBuilder->createChart(Chart::TYPE_LINE);
                $oxydantfirstProduct = $chartBuilder->createChart(Chart::TYPE_LINE);
                $oxydantsecondProduct = $chartBuilder->createChart(Chart::TYPE_LINE);
                $barrierefirstProduct = $chartBuilder->createChart(Chart::TYPE_LINE);
                $barrieresecondProduct = $chartBuilder->createChart(Chart::TYPE_LINE);

                /* HYDRATANT */
                $hydratantfirstProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $hydratantsFirstProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $hydratantsFirstProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $hydratantfirstProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($hydratantsFirstProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                $hydratantsecondProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $hydratantsSecondProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $hydratantsSecondProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $hydratantsecondProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($hydratantsSecondProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                /* OXYDANT */
                $oxydantfirstProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $antiOxydantFirstProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $antiOxydantFirstProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $oxydantfirstProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($hydratantsFirstProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                $oxydantsecondProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $antiOxydantSecondProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $antiOxydantSecondProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $oxydantsecondProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($antiOxydantSecondProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                /* BARRIERE */
                $barrierefirstProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $barriereFirstProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $barriereFirstProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $barrierefirstProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($barriereFirstProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                $oxydantsecondProduct->setData([
                    'labels' => ($session_ids),
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'Peau non traité',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $antiOxydantSecondProductZoneNonTraite,
                        ], [
                            'type' => 'line',
                            'label' => 'Peau traité',
                            'data' => $antiOxydantSecondProductZoneTraite,
                            'borderColor' => 'rgb(54, 162, 235)'
                        ]
                    ]
                ]);

                $barrieresecondProduct->setOptions([
                    'scales' => [
                        'y' => [
                            'suggestedMin' => 0,
                            'suggestedMax' => max($antiOxydantSecondProductZoneTraite) + 5,
                        ],
                    ],
                ]);

                return $this->render('test/index.html.twig', [
                    'hydratantfirstProduct' => $hydratantfirstProduct,
                    'hydratantsecondProduct' => $hydratantsecondProduct,
                    'oxydantfirstProduct' => $oxydantfirstProduct,
                    'oxydantsecondProduct' => $oxydantsecondProduct,
                    'barrierefirstProduct' => $barrierefirstProduct,
                    'barrieresecondProduct' => $barrierefirstProduct,
                ]);

                $pdfOptions = new Options();
                $pdfOptions->set('defaultFont', 'Garamond');
                $domPdf = new DomPdf();
                $domPdf->setOptions($pdfOptions);
                $domPdf->setPaper('A4', 'portrait');
                $domPdf->loadHtml($html);
                $domPdf->render();
                $domPdf->stream();
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // $studyRepository->add($study);
            return $this->redirectToRoute('dashboard_studies', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/new_study.html.twig', [
            'study' => $study,
            'form' => $form
        ]);
    }

    #[Route('/chart', name: 'chart')]
    public function chart(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $data = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);

        $data->setData([
            'labels' => [
                'January',
                'February',
                'March',
                'April'
            ],
            'datasets' => [
                [
                    'label' => 'Bar Dataset',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [10, 20, 30, 40],
                ], [
                    'type' => 'line',
                    'label' => 'Line Dataset',
                    'data' => [50, 50, 50, 50],
                    'fill' => false,
                    'borderColor' => 'rgb(54, 162, 235)'
                ]
            ]
        ]);

        $data->setOptions([
            'type' => 'scatter',
            'data' => 'data',
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]
        ]);



        return $this->render('test/index.html.twig', [
            'chart' => $chart,
            'data' => $data,
        ]);
    }
}
