<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\From;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private UserRepository $userRepository;
    public function __construct(UserPasswordHasherInterface $passwordHasher){

        $this->passwordHasher = $passwordHasher;
    }

    public function sendEmail(MailerInterface $mailer,$entityInstance){
        $user = $this->getUser();
        $identifier = $user->getUserIdentifier();
        $email = new MimeEmail();
        $email ->From($identifier)
        ->to($entityInstance->getEmail())
        ->subject('Your account was created')
        ->html('<p>This is your Identifier</p>'.$entityInstance->getEmail().'Your password'.$entityInstance->getPassword());
        $mailer->send($email);
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
//        $this->sendEmail($this->mailer,$entityInstance);
        parent::persistEntity($entityManager,$entityInstance);
    }

    public function configureFields(string $pageName): iterable
{
    return [
        EmailField:: new('email'),
        TextField::new('password')
        ->hideOnIndex(),
        TextField:: new('firstName'),
        TextField:: new('lastName'),
        TextField::new('compagny'),
        CountryField::new('country'),
//        ImageField::new('profile_picture')->setUploadDir('public/img'),
        ChoiceField::new('Roles')->allowMultipleChoices()->setChoices(["ADMIN"=>"ROLE_ADMIN","PROFESSIONNEL"=>"ROLE_PRO"]),
        BooleanField::new('actif'),
        BooleanField::new('newsletter')
    ];
}
}
