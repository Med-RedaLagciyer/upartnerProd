<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class SecurityController extends AbstractController
{

    private $passwordEncoder, $em;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, ManagerRegistry $doctrine)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $doctrine->getManager();
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    #[Route(path: '/passwordchange', name: 'app_passwordchange')]
    public function passwordchange(Request $request, ManagerRegistry $doctrine)
    {
        
        $user = $this->em->getRepository(User::class)->find($this->getUser()->getId());
        // dd($user);
        if(!$this->passwordEncoder->isPasswordValid($user, $request->get("an_password"))) {
            return new JsonResponse("Votre mot de passe est incorrect !", 500);
        }
        $user->setPassword($this->passwordEncoder->hashPassword(
            $user,
            $request->get('nv_password')
        ));

        $this->em->flush();
        return new JsonResponse("Bien Enregistre!", 200);
    }
}
