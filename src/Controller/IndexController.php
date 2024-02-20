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

        if ($security->getUser()->getValide() != 2 and $security->isGranted('ROLE_FRS')) {
            return $this->redirectToRoute('app_fournisseur_validation');
        } elseif ($security->isGranted('ROLE_FRS')) {
            $donnee = [];

            // $reclamation = $doctrine->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "SELECT COUNT(*) FROM `ua_t_commandefrscab` cab inner join u_p_partenaire p on p.id = cab.u_p_partenaire_id
            inner join ua_t_livraisonfrscab liv on liv.ua_t_commandefrscab_id = cab.id
            INNER join ua_t_facturefrscab f on f.id = liv.ua_t_facturefrscab_id
            WHERE p.ice_o like '" . $this->getUser()->getUsername() . "' and cab.active = 1 AND f.datefacture > '2023-01-01'";
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
        } else {
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
