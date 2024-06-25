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
    public function index(Security $security, ManagerRegistry $doctrine, Request $request): Response
    {
        // dd($this->getUser()->getUsername());
        if (!$security->getUser()) {
            // Redirect to the login page
            return $this->redirectToRoute('app_login');
        }

        if ($security->isGranted('ROLE_FRS') and $security->getUser()->getValide() == 1 ) {
            // Redirect to the await page
            return $this->redirectToRoute('app_fournisseur_await');
        }

        if ($security->isGranted('ROLE_FRS') and $security->getUser()->getValide() == 0 ) {
            return $this->redirectToRoute('app_fournisseur_validation');
        }

        if ($security->isGranted('ROLE_FRS') and $security->getUser()->getValide() == 2) {
            $donnee = [];

            // $reclamation = $doctrine->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('default')->getConnection();

            // $query = "SELECT COUNT(*) FROM `ua_t_commandefrscab` cab INNER JOIN u_p_partenaire p on p.id = cab.u_p_partenaire_id INNER JOIN ua_t_livraisonfrscab liv ON liv.ua_t_commandefrscab_id = cab.id INNER JOIN ua_t_facturefrscab f ON f.id = liv.ua_t_facturefrscab_id LEFT JOIN u_general_operation o on o.facture_fournisseur_id = f.id LEFT JOIN tr_transaction tr on tr.operation_id = o.id WHERE cab.active = 1 AND p.ice_o = '".$this->getUser()->getUsername()."' AND f.datefacture > '2023-01-01' and (o.id is null or o.executer is null);";
            $query = "SELECT COUNT(*) FROM (SELECT c.*
        
            FROM ua_t_commandefrscab c 
            INNER JOIN ua_t_commandefrsdet det on det.ua_t_commandefrscab_id = c.id
            
            WHERE EXISTS (SELECT 1 FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id) 
            AND EXISTS (SELECT 1 FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id) 
            AND c.u_p_partenaire_id =  " . $this->getUser()->getPartenaireId() . "  
            AND c.active = 1
            AND (SELECT f.datefacture FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) > '2023-01-01'
            AND (SELECT f.active FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) = 1 GROUP BY c.id) AS countCommandes";
            
            // dd($query);
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesCount = $result->fetchAll();

            // dd($facturesCount);
            $reclamations = $doctrine->getRepository(Reclamation::class)->findBySansReponse($this->getUser());
            // dd($reclamations);
            // $reclamationCount = $reclamation->count(['userCreated' => $this->getUser()]);
            $donnee = [
                // 'userCount' => $userCount,
                'reclamationCount' => count($reclamations),
                'factureCount' => $facturesCount[0]['COUNT(*)'],
                // 'fournisseurCount' => $fournisseurCount[0]['COUNT(*)'],
            ];
            return $this->render('index/indexfrs.html.twig', [
                'donnee' => $donnee,
            ]);
        } 
        if ($security->isGranted('ROLE_ADMIN')) {
            $donnee = [];

            $user = $doctrine->getRepository(User::class);
            $reclamation = $doctrine->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "SELECT COUNT(*) FROM `ua_t_facturefrscab`";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesCount = $result->fetchAll();

            $query2 = "SELECT COUNT(*) FROM `u_p_partenaire`";
            $statement2 = $entityManager->prepare($query2);
            $result2 = $statement2->executeQuery();
            $fournisseurCount = $result2->fetchAll();


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
