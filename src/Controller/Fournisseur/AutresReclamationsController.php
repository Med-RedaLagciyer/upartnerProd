<?php

namespace App\Controller\Fournisseur;

use App\Entity\Facture;
use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Controller\DatatablesController;
use App\Entity\Statut;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/fournisseur/autres')]
class AutresReclamationsController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_fournisseur_autres')]
    public function index(ManagerRegistry $doctrine): Response
    {


        $entityManager = $doctrine->getManager('default')->getConnection();

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


        $donnee = [
            'partenaire' => $infos[0],
            'montantTotal' => $data[0]["totalAmount"],
            'montantTotalRegle' => $data[0]["totalAmountExecuted"],
            'factureCount' => $data[0]['totalInvoices'],
            'facturesRegleCount' => $data[0]['totalExecutedInvoices'],
        ];

        return $this->render('fournisseur/autres_reclamations/index.html.twig', [
            'donnee' => $donnee,
        ]);
    }

    #[Route('/list', name: 'app_fournisseur_autres_list')]
    public function list(ManagerRegistry $doctrine, Request $request): Response
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

    #[Route('/listreclamation', name: 'app_fournisseur_autres_list')]
    public function list2(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where r.active = 1 and r.userCreated_id = " . $this->getUser()->getId();
        // dd($params->all('columns')[0]);

        $columns = array(
            array('db' => 'r.id', 'dt' => 0),
            array('db' => 'r.objet', 'dt' => 1),
            array('db' => 'r.observation', 'dt' => 2),
            array('db' => 'r.created', 'dt' => 3),
            array('db' => 'rep.message', 'dt' => 4),
            array('db' => 'UPPER(rep.created)', 'dt' => 5),
            array('db' => 'UPPER(rep.userCreated_id)', 'dt' => 6)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r LEFT JOIN reponse rep on rep.reclamation_id = r.id
        
        
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
        $sqlRequest .= " GROUP BY r.id ";
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
            // dd($row);
            // $nestedData[] = "<input type ='checkbox' class='checkreclamation' id ='$cd' value='$cd'>";
            $reponses = $this->em->getRepository(Reponse::class)->findby(['reclamation' => $row['id']], ["id" => "desc"]);
            // dd($reponses);

            $nestedData["DT_RowId"] = $cd;



            $nestedData[] = "<div class='text-truncate objet' title='" . $row['objet'] . "' style='text-align:left !important'>" . $row['objet'] . "</div>";
            $nestedData[] = "<div class='text-truncate' title='" . $row['observation'] . "' style='text-align:left !important'>" . $row['observation'] . "</div>";

            // $nestedData[] = $row['created'];
            // $nestedData[] = $row['UPPER(rep.created)'];

            if ($reponses && $reponses[0]->getUserCreated() != $this->getUser()) {
                $etat_bg = "etat_bg_blue";
                $nestedData[] = "<div class='text-truncate' title='" . $reponses[0]->getMessage() . "' style='text-align:left !important'>" . $reponses[0]->getMessage() . "</div>";
            } else {
                $etat_bg = "etat_bg_disable";
                $nestedData[] = "";
            }

            $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="local" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';


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

    #[Route('/details/{reclamation}', name: 'app_fournisseur_reclamation_details')]
    public function details(ManagerRegistry $doctrine, Reclamation $reclamation): Response
    {

        // $factures = $reclamation->getFactures();
        // // dd($factures);

        // $factures_infos = $this->render("fournisseur/autres_reclamations/pages/details.html.twig", [
        //     'factures' => $factures,
        //     'reclamation' => $reclamation,
        // ])->getContent();

        // $factures_repondre = $this->render("fournisseur/autres_reclamations/pages/repondre.html.twig", [
        //     'factures' => $factures,
        //     'reclamation' => $reclamation,
        // ])->getContent();

        // return new JsonResponse([
        //     'infos' => $factures_infos,
        //     'repondre' => $factures_repondre
        // ]);
        // else{
        //     return new JsonResponse('Y\'a aucune reclamation a cette facture!',500);
        // }

        if (count($reclamation->getFactures()) > 0) {
            $factures = $reclamation->getFactures();
            $reclamation_infos = $this->render("fournisseur/autres_reclamations/pages/details.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();

            $reclamation_repondre = $this->render("fournisseur/autres_reclamations/pages/repondre.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();
            // dd($reclamation);
            return new JsonResponse([
                'infos' => $reclamation_infos,
                'repondre' => $reclamation_repondre,
                'local' => true
            ]);
        } else {
            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "SELECT id, code, datecommande, refDocAsso FROM `ua_t_commandefrscab` WHERE id_reclamation = " . $reclamation->getId() . ";";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $commande = $result->fetchAll();
            // dd($factures);
            $reclamation_infos = $this->render("fournisseur/autres_reclamations/pages/details.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false
            ])->getContent();

            $reclamation_repondre = $this->render("fournisseur/autres_reclamations/pages/repondre.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false

            ])->getContent();
            // dd($reclamation);
            return new JsonResponse([
                'infos' => $reclamation_infos,
                'repondre' => $reclamation_repondre,
                'local' => false
            ]);
        }
    }
}
