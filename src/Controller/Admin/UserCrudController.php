<?php

namespace App\Controller\Admin;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher){

        $this->passwordHasher = $passwordHasher;
    }
    
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if(!$entityInstance instanceof User) return;
        $encoder = $this->passwordHasher->hashPassword($entityInstance,$entityInstance->getPassword());
        $entityInstance->setPassword($encoder);
        $entityInstance->setCreatedAt(new DateTimeImmutable());
        $entityInstance->setUpdatedAt(new DateTimeImmutable());
        parent::persistEntity($entityManager,$entityInstance);
    }

    public function configureFields(string $pageName): iterable
{
    return [
        EmailField:: new('email'),
        TextField::new('password'),
        TextField:: new('firstName'),
        TextField:: new('lastName'),
        TextField::new('compagny'),
        CountryField::new('country'),
        ImageField::new('profile_picture')->setUploadDir('public/img'),
        ChoiceField::new('Roles')->allowMultipleChoices()->setChoices(["ADMIN"=>"ROLE_ADMIN","PROFESSIONNEL"=>"ROLE_PRO"]),
        BooleanField::new('actif'),
        BooleanField::new('newsletter')
    ];
}
}
