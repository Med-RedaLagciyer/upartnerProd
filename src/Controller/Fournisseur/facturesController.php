<?php

namespace App\Controller\Fournisseur;

use App\Entity\Statut;
use App\Entity\Facture;
use App\Entity\Reponse;
use App\Entity\Reclamation;
use function PHPSTORM_META\type;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

        $query = "SELECT id, code , nom, prenom, societe from u_p_partenaire Where active = 1 and ice_o like '" . $this->getUser()->getUsername() . "'";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $infos = $result->fetchAll();

        // $query = "SELECT COUNT(*) FROM `ua_t_commandefrscab` cab inner join u_p_partenaire p on p.id = cab.u_p_partenaire_id
        //     inner join ua_t_livraisonfrscab liv on liv.ua_t_commandefrscab_id = cab.id
        //     INNER join ua_t_facturefrscab f on f.id = liv.ua_t_facturefrscab_id
        //     WHERE p.ice_o like '" . $this->getUser()->getUsername() . "' and cab.active = 1 AND f.datefacture > '2023-01-01'";
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
        // $query = "SELECT COUNT(*) FROM `ua_t_commandefrscab` cab INNER JOIN u_p_partenaire p on p.id = cab.u_p_partenaire_id INNER JOIN ua_t_livraisonfrscab liv ON liv.ua_t_commandefrscab_id = cab.id INNER JOIN ua_t_facturefrscab f ON f.id = liv.ua_t_facturefrscab_id LEFT JOIN u_general_operation o on o.facture_fournisseur_id = f.id LEFT JOIN tr_transaction tr on tr.operation_id = o.id WHERE cab.active = 1 AND p.ice_o = '".$this->getUser()->getUsername()."' AND f.datefacture > '2023-01-01' and (o.id is null or o.executer is null);";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $data = $result->fetchAll();
        // dd($data);

        $query = "SELECT COUNT(*) FROM `reponse` rep INNER JOIN reclamation r on r.id = rep.reclamation_id WHERE rep.userCreated_id != ".$this->getUser()->getId()." and r.userCreated_id = ".$this->getUser()->getId().";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $retour = $result->fetchAll();

        
        $reclamation = $this->em->getRepository(Reclamation::class);
        // $reponses = $reclamation->getReponses();
        // dd($this->getUser());
        
        $reclamationCount = $reclamation->count(['userCreated' => $this->getUser(), 'active' => 1]);
        $donnee = [
            'partenaire' => $infos[0],
            'commandes' => $data[0]["COUNT(*)"],
            'reclamations' => $reclamationCount,
            'retour' => $retour[0]["COUNT(*)"]
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
        $statusFilter = $request->query->get('status');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'facturer':
                    $filtre .= " AND (SELECT CASE WHEN COUNT(f.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) = 1 and (SELECT CASE WHEN COUNT(l.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) = 1 and (SELECT COUNT(o.id) FROM u_general_operation o INNER JOIN ua_t_facturefrscab f ON f.id = o.facture_fournisseur_id INNER JOIN ua_t_livraisonfrscab l ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id AND o.executer = 1) != 1";
                    break;
                case 'reception':
                    $filtre .= " AND (SELECT CASE WHEN COUNT(f.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) != 1 and (SELECT CASE WHEN COUNT(l.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) = 1 and (SELECT COUNT(o.id) FROM u_general_operation o INNER JOIN ua_t_facturefrscab f ON f.id = o.facture_fournisseur_id INNER JOIN ua_t_livraisonfrscab l ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id AND o.executer = 1) != 1";
                    break;
                case 'regle':
                    $filtre .= " AND (SELECT COUNT(o.id) FROM u_general_operation o INNER JOIN ua_t_facturefrscab f ON f.id = o.facture_fournisseur_id INNER JOIN ua_t_livraisonfrscab l ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id AND o.executer = 1) = 1";
                    break;
                case 'cree':
                    $filtre .= " AND 1=1";
                    break;
            }
        }

        $columns = array(
            array('db' => 'c.id', 'dt' => 0),
            array('db' => 'c.code', 'dt' => 1),
            array('db' => 'c.refDocAsso', 'dt' => 2),
            array('db' => 'SUM(det.quantite * det.prixunitaire * (1+IFNULL(det.tva,0)/100) * (1-IFNULL(det.remise,0)/100)) AS ttc', 'dt' => 3),
            array('db' => 'c.observation', 'dt' => 4),
            array('db' => 'c.datecommande', 'dt' => 5),
            array('db' => '(SELECT CASE WHEN COUNT(l.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) AS receptioner', 'dt' => 6),
            array('db' => '(SELECT CASE WHEN COUNT(f.id) > 0 THEN 1 ELSE 0 END FROM ua_t_livraisonfrscab l JOIN ua_t_facturefrscab f ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id LIMIT 1) AS facturer', 'dt' => 7),
            array('db' => 'c.id_reclamation', 'dt' => 8),
            array('db' => 'c.statut_reclamation_id', 'dt' => 9),
            array('db' => '(SELECT COUNT(o.id) FROM u_general_operation o INNER JOIN ua_t_facturefrscab f ON f.id = o.facture_fournisseur_id INNER JOIN ua_t_livraisonfrscab l ON l.ua_t_facturefrscab_id = f.id WHERE l.ua_t_commandefrscab_id = c.id AND o.executer = 1) AS operation_count', 'dt' => 10),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM ua_t_commandefrscab c 
        INNER JOIN ua_t_commandefrsdet det on det.ua_t_commandefrscab_id = c.id
        
        $filtre";
        $grp_by = " GROUP BY c.id";
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $sql .= $grp_by;
        // dd($sql,$sqlRequest);
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());

        // dd($newstmt->fetchAll());

        $searchCollumns = array(
            array('db' => 'c.id', 'dt' => 0),
            array('db' => 'c.code', 'dt' => 1),
            array('db' => 'c.refDocAsso', 'dt' => 2),
            array('db' => 'c.observation', 'dt' => 4),
            array('db' => 'c.datecommande', 'dt' => 5),
        );

        $where = DatatablesController::Search($request, $searchCollumns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= $grp_by;
        // dd($request->get('order'));
        $sqlRequest .= DatatablesController::Order($request, $columns);
        // dd($sqlRequest);
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();



        $data = array();
        $i = 1;
        $etat_bg = "";
        foreach ($result as $key => $row) {
            // dd($row['observation']);
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = $row['id_reclamation'] == null ? "<input type ='checkbox' class='checkfacture' id ='checkfacture' data-id='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='checkfacture' data-id='$cd'>";
            $nestedData[] = "<center>".$row['code']."</center>" ;
            $nestedData[] =  "<center>".$row['refDocAsso']."</center>";
            // $nestedData[] = $row['ttc'];
            $nestedData[] = "<div style='text-align:right !important; margin-right:1rem;'> " . number_format($row['ttc'], 2, '.', ' ') . "</div>";
            // $nestedData[] = $row['observation'];
            $nestedData[] = "<div class='text-truncate-commande' title='" . $row['observation'] . "' style='text-align:left !important'> " . $row['observation'] . "</div>";
            // $nestedData[] = "<div style='text-align:right !important; margin-right:5px !important'>" . number_format($row['montant'], 2, ',', ' ') . "</div>";
            // $nestedData[] = $row['datecommande'];
            $nestedData[] =  "<center>".$row['datecommande']."</center>";
            // $nestedData[] = $row['dateDocAsso'];



            // $sql = "SELECT * FROM u_general_operation o INNER JOIN ua_t_facturefrscab f on f.id = o.facture_fournisseur_id INNER JOIN ua_t_livraisonfrscab l on l.ua_t_facturefrscab_id = f.id where l.ua_t_commandefrscab_id = " . $row['id'] . " and o.executer = 1;";
            // // dd($sql);
            // $statement = $doctrine->getmanager('default')->getConnection()->prepare($sql);
            // $result = $statement->executeQuery();
            // $op = $result->fetchAll();

            if ($row['receptioner'] == 1 && $row['facturer'] != 1 && $row['operation_count'] != 1) {
                $nestedData[] = "<center  style='text-transform: capitalize !important;margin-bottom:0'>receptioné</center> ";
            } elseif ($row['receptioner'] == 1 && $row['facturer'] == 1 && $row['operation_count'] != 1) {
                $nestedData[] = "<center  style='text-transform: capitalize !important;margin-bottom:0'>facturé</center> ";
            } elseif ($row['operation_count'] == 1) {
                $nestedData[] = "<center  style='text-transform: capitalize !important;margin-bottom:0'>Réglé</center> ";
                // $nestedData[] = "Réglé";
            } else {
                $nestedData[] = "<center  style='text-transform: capitalize !important;margin-bottom:0'>Crée</center> ";
                // $nestedData[] = "Creé";
            }





            // $row['statut_reclamation_id'] != null ? $nestedData[] = $this->em->getRepository(Statut::class)->find($row['statut_reclamation_id'])->getDesignation() : $nestedData[] = "6";


           

            $reclamation = null;

            if ($row['id_reclamation']) {
                $reclamation = $this->em->getRepository(Reclamation::class)->find($row['id_reclamation']);
            }

            $reponse = $this->em->getRepository(Reponse::class)->findBy(
                ['reclamation' => $reclamation],
                ['created' => 'DESC'], // Order by date in descending order
                1 // Limit to only 1 result
            );
            // dd($reponse[0]->getUserCreated() != $this->getUser());

            if ($row['id_reclamation'] != null && ($reclamation and (count($reclamation->getReponses()) == 0 || $reponse[0]->getUserCreated() == $this->getUser()))) {
                $etat_bg = "etat_bg_disable";
            } else if ($row['id_reclamation'] != null && ($reclamation and $reponse[0] and $reponse[0]->getUserCreated() != $this->getUser())) {
                $etat_bg = "etat_bg_blue";
            } else {
                $etat_bg = "";
            }




            // dd($sql);



            // if ($row['id_reclamation'] == null && $row['UPPER(o.id)'] != null && $row['executer'] == 1) {
            //     $etat_bg = "etat_bg_vert";
            // }
            if ($row['id_reclamation'] == null && $row['receptioner'] == 1 && $row['facturer'] != 1) {
                $etat_bg = "etat_bg_receptioner";
            }
            if ($row['id_reclamation'] == null && $row['receptioner'] == 1 && $row['facturer'] == 1) {
                $etat_bg = "etat_bg_facturer";
            }


            if ($row['operation_count'] == 1) {
                // dd("hi");
                $etat_bg = "etat_bg_regle";
            }

            $nestedData[] = $row['id_reclamation'] == null ? '<div cl="bg" class="'.$etat_bg.'">
                <a data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a>
                <div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;">
                    <a data-value="default" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Détails</a>
                    <a data-value="default" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-file mr-2"></i> Réclamation</a> 
                </div>
            </div>' : 
            '<div cl="bg" class="'.$etat_bg.'">
                <a data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a>
                <div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;">
                    <a data-value="default" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye ml-1 mr-2"></i> Détails</a>
                    <a data-value="default" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-file ml-2 mr-2"></i> Réclamation</a>
                </div>
            </div>';

            $nestedData["DT_RowId"] = $cd;
            // $nestedData["DT_RowClass"] = $etat_bg;
            $data[] = $nestedData;
            $i++;
        }
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        );
        // dd($json_data);
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
            // dd($query);
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

        $query = "SELECT Cm.id, Cm.code, Cm.datecommande, F.montant As montant_facture, Op.montant As montant_regle FROM `Ua_t_commandefrscab` Cm
        INNER JOIN Ua_t_livraisonfrscab Liv On Liv.Ua_t_commandefrscab_id = Cm.id
        INNER JOIN Ua_t_facturefrscab F On F.Id = Liv.ua_t_facturefrscab_id 
        LEFT JOIN U_general_operation Op On Op.facture_fournisseur_id = F.id
        WHERE cm.id = " . $commande_id . ";";
        // dd($query);
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $commande = $result->fetchAll();

        // dd($commande);

        $query = "SELECT id, code, datelivraison, refDocAsso FROM `ua_t_livraisonfrscab` WHERE ua_t_commandefrscab_id  = " . $commande_id . ";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $reception = $result->fetchAll();

        // dd($reception);

        $query = "SELECT f.id, f.code, f.datefacture, f.refDocAsso, f.montant, o.executer FROM `ua_t_facturefrscab` f 
        INNER JOIN ua_t_livraisonfrscab l on l.ua_t_facturefrscab_id = f.id
        LEFT JOIN u_general_operation o on o.facture_fournisseur_id = f.id
        where l.ua_t_commandefrscab_id =" . $commande_id . ";";
        // dd($query);
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $facture = $result->fetchAll();

        // dd($commande, $reception, $facture);


        $commande_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
            // 'facture' => $facture,
            'commande' => $commande,
            // 'reception' => $reception,
        ])->getContent();
        $facture_infos = $this->render("fournisseur/factures/pages/detailsUgouvFacture.html.twig", [
            'facture' => $facture,
            // 'commande' => $commande,
            // 'reception' => $reception,
        ])->getContent();
        $livraison_infos = $this->render("fournisseur/factures/pages/detailsUgouvReception.html.twig", [
            // 'facture' => $facture,
            // 'commande' => $commande,
            'reception' => $reception,
        ])->getContent();
        // dd($dets);
        return new JsonResponse([
            'infos_commande' => $commande_infos,
            'infos_facture' => $facture_infos,
            'infos_livraison' => $livraison_infos,
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
            // dd($query);
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
                    'infos' => $factures_infos,
                    'objetReclamationDetail' => $reclamation[0]->getObjet()
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

            // dd($reclamation[0]->getObjet());
            if ($reclamation) {

                // dd($reclamation);
                $factures_infos = $this->render("fournisseur/factures/pages/detailsReclamation.html.twig", [
                    'reclamation' => $reclamation[0],
                    'rec' => true,
                    'type' => $type
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos,
                    'objetReclamationDetail' => $reclamation[0]->getObjet()
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
        $commandes = [];
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
        // dd($request->files->get('file'));
        if ($request->get("message") || $request->files->get('file')) {
            $reclamation = $this->em->getRepository(Reclamation::class)->find($request->get("reclamation"));
            // dd($reclamation);
            // dd('hi');
            $reponse = new Reponse();



            if ($request->get("message")) $reponse->setMessage($request->get("message"));
            if ($request->files->get('file')) {
                $file = $request->files->get('file');

                $uploadedDirectory = $this->getParameter('message_directory');
                $fileName = uniqid() . '.' . $file->guessExtension();

                $file->move($uploadedDirectory, $fileName);
                $reponse->setFile($fileName);
            }
            $reponse->setReclamation($reclamation);


            $reponse->setUserCreated($this->getUser());
            $reponse->setCreated(new \DateTime());
            $reponse->setAdmin(false);

            $this->em->persist($reponse);

            $this->em->flush();


            return new JsonResponse([
                'message' => $reponse->getMessage(),
                'date' => $reponse->getCreated()->format('d/m/Y'),
                'file' => $reponse->getFile()
            ]);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES (MESSAGE / PIECE JOINTE)', 500);
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
            if ($file) {
                $uploadedDirectory = $this->getParameter('facture_directory');
                $fileName = uniqid() . '.' . $file->guessExtension();

                $file->move($uploadedDirectory, $fileName);
                $facture->setFile($fileName);
            }

            $this->em->persist($facture);
            $this->em->flush();


            return new JsonResponse('FACTURES BIEN ENVOYÉE', 200);
        }
    }

    #[Route('/extraction', name: 'extraction_factures')]
    public function extraction_historique(ManagerRegistry $doctrine,)
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        // $query = "SELECT
        // c.CODE 'CODE BC',
        // c.datecommande,
        // c.autre_information 'AUTRE INFORMATION BC',
        // c.position_actuel 'POSITION ACTUEL BC',
        // r.CODE 'ID BR',
        // r.datelivraison,
        // r.description,
        // r.position_actuel 'POSITION ACTUEL BR',
        // f.CODE 'CODE FAF',
        // f.montant 'montant FAF',
        // f.datefacture,
        // f.created 'DATE CEATION FAF',
        // f.autre_information 'AUTRE INFORMATION FAF',
        // f.source,
        // f.position_actuel 'POSITION ACTUEL FAF',
        // f.observation,
        // f.refDocAsso
        // FROM
        // `ua_t_commandefrscab` c
        // INNER JOIN u_p_partenaire p2 ON p2.id = c.u_p_partenaire_id
        // LEFT JOIN ua_t_livraisonfrscab r ON r.ua_t_commandefrscab_id = c.id
        // LEFT JOIN ua_t_facturefrscab f ON f.id = r.ua_t_facturefrscab_id
        
        // WHERE
        //  c.u_p_partenaire_id = " . $this->getUser()->getPartenaireId() . " and f.active = 1 and f.datefacture > '2023-01-01';";
        
        $query = "SELECT
        cab.code, 
        DATE_FORMAT(cab.datecommande,'%d/%m/%Y')  datecommande , 
        SUM(ROUND(det.quantite * det.prixunitaire * (1+IFNULL(det.tva,0)/100) * (1-IFNULL(det.remise,0)/100), 2)) TTC  


        FROM `ua_t_commandefrscab` cab
        left join `u_p_partenaire` frs on frs.id = cab.u_p_partenaire_id
        left join ua_t_commandefrsdet det on det.ua_t_commandefrscab_id = cab.id
        where 1= 1 and cab.active = 1 and cab.u_p_partenaire_id = " . $this->getUser()->getPartenaireId() . ";";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $data = $result->fetchAll();

        // dd($data);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();


        // $headerRow = ['CODE BC', 'Date Commande', 'Autre Information BC', 'Position Actuel BC', 'ID BR', 'Date Livraison', 'Description', 'Position Actuel BR', 'CODE FAF', 'Montant FAF', 'Date Facture', 'Date Creation FAF', 'Autre Information FAF', 'Source', 'Position Actuel FAF', 'Observation', 'RefDocAsso'];
        $headerRow = ['CODE BC', 'Date Commande', 'TTC'];
        $sheet->fromArray([$headerRow], null, 'A1');

        // Add data rows
        $dataRows = [];
        foreach ($data as $row) {
            $dataRows[] = array_values($row);
        }
        $sheet->fromArray($dataRows, null, 'A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = "Extraction Factures Fournisseur:" . $this->getUser()->getPartenaireId() . ".xlsx";
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
