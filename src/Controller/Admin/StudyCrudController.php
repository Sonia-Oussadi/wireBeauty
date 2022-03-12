<?php

namespace App\Controller\Admin;

use App\Entity\Compagny;
use App\Entity\Study;
use App\Form\StudyType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag as HttpFoundationParameterBag;
use Symfony\Component\Form\FormBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class StudyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Study::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Study')
            ->setEntityLabelInPlural('Studies')
            ->setSearchFields(['name', 'created_at'])
            ->setDefaultSort(['created_at' => 'DESC'])

            ->setPageTitle('new', 'Generate study')
        ;
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('name')->setColumns(3),
            NumberField::new('price')->setNumDecimals(2)->formatValue(function ($value) {
                return $value . " $";
            }),
            DateTimeField::new('created_at')->hideOnForm(),
            BooleanField::new('available', 'Available to buy'),
            BooleanField::new('private'),
        ];
    }

    public function createEntity(string $entityFqcn)
    {

        // dd('s');
        // return $this->redirect('/about');
        // $study = new Study();
        // $study->setCreator($this->getUser());
        // $study->setUpdatedAt(new \DateTimeImmutable());
        // $study->setCreatedAt(new \DateTimeImmutable());

        // return $study;
    }

    public function configureActions(Actions $actions): Actions
    {

        $viewStudy = Action::new('viewStudy', 'Show')
            ->setHtmlAttributes(['target' => '_blank'])
        ->linkToRoute('app_view_study', function (Study $entity) {
            return [
                'id' => $entity->getId(),
            ];
        });

        $downloadStudy = Action::new('downloadStudy', 'Download')
        ->linkToRoute('app_download_study', function (Study $entity) {
            return [
                'id' => $entity->getId(),
            ];
        });

         return $actions
            ->add(Crud::PAGE_INDEX, $viewStudy)
            ->add(Crud::PAGE_INDEX, $downloadStudy);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = $this->container->get(FormFactory::class)->createEditFormBuilder($entityDto, $formOptions, $context);
        $formBuilder->add('compagny', EntityType::class, [
            'class' => Compagny::class,
            'choice_label' => 'name',
        ]);

        return $formBuilder;
    }
   
}
