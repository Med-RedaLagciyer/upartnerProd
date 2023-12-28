<?php

namespace App\Controller\Fournisseur;

use App\Entity\Statut;
use App\Entity\Facture;
use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use function PHPSTORM_META\type;

#[Route('/fournisseur/factures')]
class facturesController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_fournisseur_factures')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        // dd($this->getUser());

        $query = "SELECT id, code , nom, prenom from u_p_partenaire Where active = 1 and ice_o like '" . $this->getUser()->getUsername() . "'";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $infos = $result->fetchAll();

        $query = "SELECT 
            COUNT(*) AS totalInvoices,
            SUM(CASE WHEN op.executer = 1 THEN 1 ELSE 0 END) AS totalExecutedInvoices,
            SUM(cab.montant) AS totalAmount,
            SUM(CASE WHEN op.executer = 1 THEN cab.montant ELSE 0 END) AS totalAmountExecuted
            FROM 
                `ua_t_facturefrscab` cab
            INNER JOIN 
                `u_p_partenaire` p ON p.id = cab.partenaire_id
            LEFT JOIN 
                `u_general_operation` op ON op.facture_fournisseur_id = cab.id
            WHERE 
                p.id = " . $infos[0]["id"] . "
                AND cab.active = 1 
                AND cab.datefacture > '2023-01-01'";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $data = $result->fetchAll();

        $reclamation = $this->em->getRepository(Reclamation::class);

        $reclamationCount = $reclamation->count(['userCreated' => $this->getUser()]);
        $donnee = [
            'partenaire' => $infos[0],
            'montantTotal' => $data[0]["totalAmount"],
            'montantTotalRegle' => $data[0]["totalAmountExecuted"],
            'factureCount' => $data[0]['totalInvoices'],
            'facturesRegleCount' => $data[0]['totalExecutedInvoices'],
        ];
        return $this->render('fournisseur/factures/index.html.twig', [
            'donnee' => $donnee,
        ]);
    }

    #[Route('/list', name: 'app_fournisseur_factures_list')]
    public function list(ManagerRegistry $doctrine, Request $request): Response
    {


        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        // dd($this->getUser());


        $filtre = "WHERE EXISTS (SELECT 1 FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id) 
        AND EXISTS (SELECT 1 FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id) 
        AND c.u_p_partenaire_id = " . $this->getUser()->getPartenaireId() . " 
        AND c.active = 1
        AND (SELECT f.datefacture FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) > '2023-01-01'
        AND (SELECT f.active FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) = 1";

        // dd($filtre);

        $columns = array(
            array('db' => 'c.id', 'dt' => 0),
            array('db' => 'c.code', 'dt' => 1),
            array('db' => 'c.refDocAsso', 'dt' => 2),
            array('db' => 'SUM(det.quantite * det.prixunitaire * (1+IFNULL(det.tva,0)/100) * (1-IFNULL(det.remise,0)/100)) ttc', 'dt' => 3),
            array('db' => 'c.observation', 'dt' => 4),
            array('db' => 'c.datecommande', 'dt' => 5),
            array('db' => '(SELECT CASE WHEN COUNT(l.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) AS receptioner', 'dt' => 6),
            array('db' => '(SELECT CASE WHEN COUNT(f.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) AS facturer', 'dt' => 7),
            array('db' => 'c.id_reclamation', 'dt' => 8),
            array('db' => 'c.statut_reclamation_id', 'dt' => 9),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM ua_t_commandefrscab c 
        INNER JOIN ua_t_commandefrsdet det on det.ua_t_commandefrscab_id = c.id
        
        $filtre GROUP BY c.id";
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());

        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= DatatablesController::Order($request, $columns);
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();

        // dd($sql);


        $data = array();
        $i = 1;
        $etat_bg = "";
        foreach ($result as $key => $row) {
            // dd($row['observation']);
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = $row['id_reclamation'] == null ? "<input type ='checkbox' class='checkfacture' id ='checkfacture' data-id='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='checkfacture' data-id='$cd'>";
            $nestedData[] = $row['code'];
            $nestedData[] = $row['refDocAsso'];
            $nestedData[] = $row['ttc'];
            $nestedData[] = "<div style='text-align:right !important'> " . number_format($row['ttc'], 2, '.', ' ') . "</div>";
            // $nestedData[] = $row['observation'];
            $nestedData[] = "<div class='text-truncate-commande' title='" . $row['observation'] . "' style='text-align:left !important'> " . $row['observation'] . "</div>";
            // $nestedData[] = "<div style='text-align:right !important; margin-right:5px !important'>" . number_format($row['montant'], 2, ',', ' ') . "</div>";
            $nestedData[] = $row['datecommande'];
            // $nestedData[] = $row['dateDocAsso'];

            if ($row['receptioner'] == 1 && $row['facturer'] != 1) {
                $nestedData = "receptioné";
            } elseif ($row['receptioner'] == 1 && $row['facturer'] == 1) {
                $nestedData[] = "facturé";
            } else {
                $nestedData[] = "Creé";
            }

            // $row['statut_reclamation_id'] != null ? $nestedData[] = $this->em->getRepository(Statut::class)->find($row['statut_reclamation_id'])->getDesignation() : $nestedData[] = "6";


            $nestedData[] = $row['id_reclamation'] == null ? '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="default" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="default" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>' : '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"  style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="default" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="default" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';

            $reclamation = null;

            if ($row['id_reclamation']) {
                $reclamation = $this->em->getRepository(Reclamation::class)->find($row['id_reclamation']);
            }

            if ($row['id_reclamation'] != null && ($reclamation and count($reclamation->getReponses()) == 0)) {
                $etat_bg = "etat_bg_disable";
            } else if ($row['id_reclamation'] != null && ($reclamation and $reclamation->getReponses())) {
                $etat_bg = "etat_bg_blue";
            } else {
                $etat_bg = "";
            }


            // if ($row['id_reclamation'] == null && $row['UPPER(o.id)'] != null && $row['executer'] == 1) {
            //     $etat_bg = "etat_bg_vert";
            // }
            if ($row['id_reclamation'] == null && $row['receptioner'] == 1 && $row['facturer'] != 1) {
                $etat_bg = "etat_bg_receptioner";
            }
            if ($row['id_reclamation'] == null && $row['receptioner'] == 1 && $row['facturer'] == 1) {
                $etat_bg = "etat_bg_facturer";
            }

            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $etat_bg;
            $data[] = $nestedData;
            $i++;
        }
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        );
        return new Response(json_encode($json_data));
    }

    #[Route('/list2', name: 'app_fournisseur_factures_list2')]
    public function list2(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where f.userCreated_id = " . $this->getUser()->getId() . " and f.active = 1";
        // dd($params->all('columns')[0]);

        $columns = array(
            array('db' => 'f.id', 'dt' => 0),
            array('db' => 'f.numFacture', 'dt' => 1),
            array('db' => 'f.montant', 'dt' => 2),
            array('db' => 'f.observation', 'dt' => 3),
            array('db' => 'f.datefacture', 'dt' => 4),
            array('db' => 'f.reclamation_id', 'dt' => 5),
            array('db' => 'st.designation', 'dt' => 6),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM facture f
        left join statut st on st.id = f.statut_id
        
        $filtre ";
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
        // dd($sql);

        // search 
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= DatatablesController::Order($request, $columns);
        // dd($sqlRequest);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();


        $data = array();
        // dd($result);
        $i = 1;
        $etat_bg = "";
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];

            $reclamation = null;

            if ($row['reclamation_id']) {
                $reclamation = $this->em->getRepository(Reclamation::class)->find($row['reclamation_id']);
            }
            // dd($reclamation[0]->getReponses());
            $nestedData[] = $row['reclamation_id'] == null ? "<input type ='checkbox' class='checkfacture' id ='$cd' value='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='$cd' value='$cd'>";
            $nestedData[] = $row['numFacture'];
            $nestedData[] = $row['montant'];
            $nestedData[] = $row['observation'];
            $nestedData[] = $row['datefacture'];
            $nestedData[] = $row['designation']; //statut designation
            $nestedData[] = $row['reclamation_id'] == null ? '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="local" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>' : '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"  style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="local" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';


            if ($row['reclamation_id'] != null && ($reclamation and count($reclamation->getReponses()) == 0)) {
                $etat_bg = "etat_bg_disable";
            } else if ($row['reclamation_id'] != null && ($reclamation and $reclamation->getReponses())) {
                $etat_bg = "etat_bg_blue";
            } else {
                $etat_bg = "";
            }



            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $etat_bg;
            $data[] = $nestedData;
            $i++;
        }
        // dd($data);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        );
        // die;
        return new Response(json_encode($json_data));
    }

    #[Route('/details/{factureCab}/{type}', name: 'app_fournisseur_factures_details')]
    public function details(ManagerRegistry $doctrine, $factureCab, $type): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        if ($type == "local") {
            $facture = $this->em->getRepository(Facture::class)->find($factureCab);
            $reclamation = $facture->getReclamation();

            if ($reclamation) {

                $factures_infos = $this->render("fournisseur/factures/pages/detailsLocal.html.twig", [
                    'facture' => $facture,
                    'reclamation' => $reclamation,
                ])->getContent();

                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            } else {
                return new JsonResponse('AUCUNE RÉCLAMATION', 500);
            }
        } else {
            // dd($factureCab);
            $query = "SELECT id, id_reclamation FROM `ua_t_facturefrscab` where id =" . $factureCab;
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $cab = $result->fetchAll();

            $query = "SELECT cab.id_reclamation, cab.montant as montant, cab.datefacture as datefacture, cab.observation,  ar.titre as article, u.designation as unite, det.quantite, det.prixunitaire , det.tva FROM `ua_t_facturefrsdet` det
            left JOIN ua_t_facturefrscab cab on cab.id = det.ua_t_facturefrscab_id
            LEFT JOIN uarticle ar on ar.id = det.u_article_id
            LEFT JOIN p_unite u on u.id = det.p_unite_id where cab.id =" . $factureCab;
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $dets = $result->fetchAll();

            $details = !empty($dets) ? $dets[0] : [];

            $reclamation = $this->em->getRepository(Reclamation::class)->findby(['id' => $cab[0]['id_reclamation']]);
            if ($reclamation) {
                // dd($reclamation);
                $factures_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
                    'dets' => $details,
                    'reclamation' => $reclamation[0],
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            } else {
                $factures_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
                    'dets' => $details,
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }
        }
    }
    #[Route('/detailsCommande/{commande_id}/{type}', name: 'app_fournisseur_factures_detailsCommande')]
    public function detailsCommande(ManagerRegistry $doctrine, $commande_id, $type): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        $query = "SELECT id, code, datecommande, refDocAsso FROM `ua_t_commandefrscab` WHERE id = " . $commande_id . ";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $commande = $result->fetchAll();

        $query = "SELECT id, code, datelivraison, refDocAsso FROM `ua_t_livraisonfrscab` WHERE ua_t_commandefrscab_id  = " . $commande_id . ";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $reception = $result->fetchAll();

        $query = "SELECT f.id, f.code, f.datefacture, f.refDocAsso, f.montant, o.executer FROM `ua_t_facturefrscab` f 
        INNER JOIN ua_t_livraisonfrscab l on l.ua_t_facturefrscab_id = f.id
        LEFT JOIN u_general_operation o on o.facture_fournisseur_id = f.id
        where l.ua_t_commandefrscab_id =" . $commande_id . ";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $facture = $result->fetchAll();

        // dd($commande, $reception, $facture);


        $factures_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
            'facture' => $facture,
            'commande' => $commande,
            'reception' => $reception,
        ])->getContent();
        // dd($dets);
        return new JsonResponse([
            'infos' => $factures_infos
        ]);
    }
    #[Route('/dets/{id}/{type}', name: 'app_fournisseur_factures_dets')]
    public function dets(ManagerRegistry $doctrine, $id, $type): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        if ($type == "commandeDet") {
            $query = "SELECT a.titre, d.tva, d.quantite, d.prixunitaire FROM `ua_t_commandefrsdet` d
            INNER JOIN uarticle a on a.id = d.u_article_id
            WHERE ua_t_commandefrscab_id =  " . $id . ";";
        }
        if ($type == "receptionDet") {
            $query = "SELECT a.titre, d.tva, d.quantite, d.prixunitaire FROM `ua_t_livraisonfrsdet` d
            INNER JOIN uarticle a on a.id = d.u_article_id
            WHERE ua_t_livraisonfrscab_id =  " . $id . ";";
        }
        if ($type == "factureDet") {
            $query = "SELECT a.titre, d.tva, d.quantite, d.prixunitaire FROM `ua_t_facturefrsdet` d
            INNER JOIN uarticle a on a.id = d.u_article_id
            WHERE ua_t_facturefrscab_id =  " . $id . ";";
        }


        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $dets = $result->fetchAll();

        $factures_infos = $this->render("fournisseur/factures/pages/dets.html.twig", [
            'dets' => $dets,
        ])->getContent();
        // dd($dets);
        return new JsonResponse([
            'infos' => $factures_infos
        ]);
    }

    #[Route('/reclamation/{cab}/{type}', name: 'app_fournisseur_factures_reclamation')]
    public function reclamation(ManagerRegistry $doctrine, $cab, $type): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        if ($type == "local") {
            $facture = $this->em->getRepository(Facture::class)->find($cab);
            $reclamation = $facture->getReclamation();
            if ($reclamation) {

                $factures_infos = $this->render("fournisseur/factures/pages/detailsReclamation.html.twig", [
                    'facture' => $facture,
                    'reclamation' => $reclamation,
                    'rec' => true,
                    'type' => $type
                ])->getContent();

                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            } else {
                return new JsonResponse('AUCUNE RÉCLAMATION', 500);
            }
        } else {
            $query = "SELECT id, id_reclamation FROM `ua_t_commandefrscab` where id =" . $cab;
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $cab = $result->fetchAll();
            $reclamation = $this->em->getRepository(Reclamation::class)->findby(['id' => $cab[0]['id_reclamation']]);

            if ($reclamation) {

                // dd($reclamation);
                $factures_infos = $this->render("fournisseur/factures/pages/detailsReclamation.html.twig", [
                    'reclamation' => $reclamation[0],
                    'rec' => true,
                    'type' => $type
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            } else {
                return new JsonResponse('AUCUNE RÉCLAMATION', 500);
            }
        }
    }

    #[Route('/reclamer', name: 'app_fournisseur_commandes_reclamer')]
    public function ajouter(Request $request, ManagerRegistry $doctrine): Response
    {
        // dd($request->get("observation"), $request->get("objet"));
        if ($request->get("observation") && $request->get("objet")) {
            if ($request->get("commandes")) {
                $commandes = array_unique(json_decode($request->get("commandes")));
            } else {
                $factucommandesres = [];
            }


            $reclamation = new Reclamation();

            $reclamation->setObservation($request->get("observation"));
            $reclamation->setObjet($request->get("objet"));


            $reclamation->setUserCreated($this->getUser());
            $reclamation->setCreated(new \DateTime());

            $this->em->persist($reclamation);

            $this->em->flush();

            $reclamationId = $reclamation->getId();

            if ($commandes) {
                foreach ($commandes as $commande) {
                    // dd($facture);
                    $entityManager = $doctrine->getManager('default')->getConnection();
                    $query = "UPDATE ua_t_commandefrscab  SET id_reclamation = " . $reclamation->getId() . ", statut_reclamation_id = 2 where id = " . $commande;
                    $statement = $entityManager->prepare($query);
                    $result = $statement->executeQuery();
                }
            }


            return new JsonResponse(['message' => 'RÉCLAMATION BIEN ENVOYÉE', 'reclamation_id' => $reclamationId], 200);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES', 500);
        }
    }

    #[Route('/message', name: 'app_fournisseur_factures_message')]
    public function repondre(Request $request, ManagerRegistry $doctrine): Response
    {
        // dd($request);
        if ($request->get("message")) {
            $reclamation = $this->em->getRepository(Reclamation::class)->find($request->get("reclamation"));
            // dd($reclamation);
            // dd('hi');
            $reponse = new Reponse();

            $reponse->setMessage($request->get("message"));
            $reponse->setReclamation($reclamation);


            $reponse->setUserCreated($this->getUser());
            $reponse->setCreated(new \DateTime());
            $reponse->setAdmin(false);

            $this->em->persist($reponse);

            $this->em->flush();


            return new JsonResponse([
                'message' => $reponse->getMessage(),
                'date' => $reponse->getCreated()->format('d/m/Y'),
            ]);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES', 500);
        }
    }

    #[Route('/ajouter', name: 'app_fournisseur_factures_ajouter')]
    public function ajouterFactures(Request $request, ManagerRegistry $doctrine): Response
    {
        $numFacture = $request->request->get('numFacture');
        $date = $request->request->get('date');
        $montant = $request->request->get('montant');
        $reclamationId = $request->request->get('reclamation_id');
        $file = $request->files->get('file');

        $uploadedDirectory = $this->getParameter('facture_directory');
        $fileName = uniqid() . '.' . $file->guessExtension();

        $file->move($uploadedDirectory, $fileName);

        $reclamation = $this->em->getRepository(Reclamation::class)->find($reclamationId);
        $statut = $this->em->getRepository(Statut::class)->find(2);

        if ($request->get("numFacture") && $request->get("date") && $request->get("montant")) {

            $facture = new Facture();
            $facture->setNumFacture($numFacture);
            $facture->setMontant($montant);
            $facture->setDateFacture(new \DateTime($date));
            $facture->setCreated(new \DateTime());
            $facture->setUserCreated($this->getUser());
            $facture->setReclamation($reclamation);
            $facture->setStatut($statut);
            $this->em->persist($facture);
            $facture->setFile($fileName);

            $this->em->flush();


            return new JsonResponse('FACTURES BIEN ENVOYÉE', 200);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES', 500);
        }
    }
}
