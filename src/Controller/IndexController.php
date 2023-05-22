<?php

namespace App\Controller;

use App\Entity\Reclamation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Security $security,ManagerRegistry $doctrine, Request $request): Response
    {
        if (!$security->getUser()) {
            // Redirect to the login page
            return $this->redirectToRoute('app_login');
        }

        if($security->getUser()->getValide() != 2 and $security->isGranted('ROLE_FRS')){
            return $this->redirectToRoute('app_fournisseur_validation');
           
        } elseif ($security->isGranted('ROLE_FRS')){
            $donnee = [];

            $reclamation = $doctrine->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('ugouv')->getConnection();

            $query = "SELECT COUNT(*) FROM `ua_t_facturefrscab` cab inner join u_p_partenaire p on p.id = cab.partenaire_id WHERE p.code like '".$this->getUser()->getUsername()."' and cab.active = 1 ";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesCount = $result->fetchAll();

            // dd($facturesCount);

            $reclamationCount = $reclamation->count(['userCreated' => $this->getUser()]);
            $donnee = [
                // 'userCount' => $userCount,
                'reclamationCount' => $reclamationCount,
                'factureCount' => $facturesCount[0]['COUNT(*)'],
                // 'fournisseurCount' => $fournisseurCount[0]['COUNT(*)'],
            ];
            return $this->render('index/indexfrs.html.twig', [
                'donnee' => $donnee,
            ]);
        }else {
            $donnee = [];

            $user = $doctrine->getRepository(User::class);
            $reclamation = $doctrine->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('ugouv')->getConnection();

            $query = "SELECT COUNT(*) FROM `ua_t_facturefrscab`";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesCount = $result->fetchAll();

            $query2 = "SELECT COUNT(*) FROM `u_p_partenaire`";
            $statement2 = $entityManager->prepare($query2);
            $result2 = $statement2->executeQuery();
            $fournisseurCount = $result2->fetchAll();

            // dd($facturesCount);


            $userCount = $user->count([]);
            $reclamationCount = $reclamation->count([]);
            $donnee = [
                'userCount' => $userCount,
                'reclamationCount' => $reclamationCount,
                'factureCount' => $facturesCount[0]['COUNT(*)'],
                'fournisseurCount' => $fournisseurCount[0]['COUNT(*)'],
            ];

            return $this->render('index/index.html.twig', [
                'donnee' => $donnee,
            ]);
        }
        
        
    }
}