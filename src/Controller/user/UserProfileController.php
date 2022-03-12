<?php
namespace App\Controller\user;


use App\Form\user\EditProfilType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController 
{
    
    #[Route('/user/profil', name: 'user_profil')]
    public function index(): Response
    {
        return $this->render('user/profil/profil.html.twig', [
        ]);
    }

    
    #[Route('/user/profil/edit', name: 'user_profil_edit')] 
    public function editProfile(Request $request,EntityManagerInterface $entityManager) 
    {
        $user = $this->getUser();
        $form = $this->createForm(EditProfilType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){ /* @phpstan-ignore-line */
           
            $entityManager->persist($user); 
            $entityManager->flush();

            $this->addFlash('message', 'Profil mis à jour'); /* @phpstan-ignore-line */
            return $this->redirectToRoute('user_profil');
        }

        return $this->render('user/profil/editprofil.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/pass/edit', name: 'user_pass_edit')]
    public function editPass(Request $request, UserPasswordHasherInterface $passwordEncoder,EntityManagerInterface $entityManager,UserRepository $userRepository) 
    {
        if($request->isMethod('POST')){
            //$user = $this->getUser();
            $userId = $userRepository->findIdUser($this->getUser()->getUserIdentifier()); 
            $user = $userRepository->find($userId);
            // On vérifie si les 2 mots de passe sont identiques
            if($request->request->get('pass') == $request->request->get('pass1')){
                $user->setPassword($passwordEncoder->hashPassword($user, $request->request->get('pass'))); 
                $entityManager->flush();
                $this->addFlash('message', 'Mot de passe mis à jour avec succès');
            }else{
                $this->addFlash('error', 'Les deux mots de passe ne sont pas identiques'); /* @phpstan-ignore-line */
            }
        }

        return $this->render('user/profil/editpass.html.twig');
    }

}
