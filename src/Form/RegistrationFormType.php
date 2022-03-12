<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                'constraints' => new Length(2, 8),
                'attr' => [
                    'placeholder' => 'exemple@mail.com'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped'=>false,
                'attr'=> ['placeholder'=>'indiquez votre mot de passe'],
                'constraints'=>[
                    new NotBlank([
                        'message'=> 'Please enter a password'
                    ]),
                    new Length([
                        'min'=>6,
                        'minMessage'=> 'Your password should be at least 6 characters',
                        'max'=>4096
                    ])
                ]
            ])

            ->add('firstName', TextType::class, [
                'label' => 'Prénom :',
                'constraints' => new Length(2, 2),
                'attr' => [
                    'placeholder' => 'Veuillez saisir votre prénom'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom :',
                'constraints' => new Length(2, 2),
                'attr' => [
                    'placeholder' => 'Veuillez saisir votre nom'
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "J'ai lu et j'accepte les conditions générales d'utilisation",
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => "Veilliez vous accepter les conditions générales d'utilisation.",
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => "S'inscrire",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}



