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

        $partenaire = [];

            $reclamation = $this->em->getRepository(Reclamation::class);

            $entityManager = $doctrine->getManager('ugouv')->getConnection();

            $query = "SELECT COUNT(*) FROM `ua_t_facturefrscab` cab inner join u_p_partenaire p on p.id = cab.partenaire_id WHERE p.code like '".$this->getUser()->getUsername()."' and cab.active = 1 ";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesCount = $result->fetchAll();

            $query = "SELECT COUNT(*) FROM `ua_t_facturefrscab` cab inner join u_p_partenaire p on p.id = cab.partenaire_id inner join u_general_operation op on op.facture_fournisseur_id = cab.id WHERE op.executer = 1 and p.code like '".$this->getUser()->getUsername()."' and cab.active = 1 ";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $facturesRegleCount = $result->fetchAll();

            $query = "SELECT SUM(montant) AS total_sum FROM ua_t_facturefrscab cab inner join u_p_partenaire p on p.id = cab.partenaire_id WHERE p.code like '".$this->getUser()->getUsername()."' and cab.active = 1 ";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $montantTotal = $result->fetchAll();

            $query = "SELECT SUM(cab.montant) AS total_sum FROM ua_t_facturefrscab cab inner join u_p_partenaire p on p.id = cab.partenaire_id inner join u_general_operation op on op.facture_fournisseur_id = cab.id WHERE op.executer = 1 and p.code like '".$this->getUser()->getUsername()."' and cab.active = 1 ";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $montantTotalRegle = $result->fetchAll();

            $query = "SELECT  code , nom, prenom from u_p_partenaire Where active = 1 and code like '".$this->getUser()->getUsername()."'";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $partenaire = $result->fetchAll();

            // dd($facturesRegleCount);

            $reclamationCount = $reclamation->count(['userCreated' => $this->getUser()]);
            $donnee = [
                'partenaire' => $partenaire[0],
                'montantTotal' => $montantTotal[0]["total_sum"],
                'montantTotalRegle' => $montantTotalRegle[0]["total_sum"],
                'factureCount' => $facturesCount[0]['COUNT(*)'],
                'facturesRegleCount' => $facturesRegleCount[0]['COUNT(*)'],
            ];
            return $this->render('fournisseur/factures/index.html.twig', [
                'donnee' => $donnee,
            ]);
    }

    #[Route('/list', name: 'app_fournisseur_factures_list')]
    public function list(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where p.code like '".$code."' and f.active = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'f.id','dt' => 0),
            array( 'db' => 'f.code','dt' => 1),
            array( 'db' => 'f.refDocAsso','dt' => 2),
            array( 'db' => 'f.montant','dt' => 3),
            array( 'db' => 'f.datefacture','dt' => 4),
            array( 'db' => 'f.dateDocAsso','dt' => 5),
            array( 'db' => 'f.id_reclamation','dt' => 6),
            
            array( 'db' => 'UPPER(o.id)','dt' =>7),
            array( 'db' => 'o.executer','dt' => 8),
            array( 'db' => 'f.statut_reclamation_id','dt' => 9),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM ua_t_facturefrscab f 
        inner join u_p_partenaire p on p.id = f.partenaire_id
        left join u_general_operation o on o.facture_fournisseur_id = f.id
        
        $filtre "
        ;
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $doctrine->getmanager('ugouv')->getConnection()->prepare($sql);
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
        $stmt = $doctrine->getmanager('ugouv')->getConnection()->prepare($sqlRequest);
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
            // $nestedData[] = "<input type ='checkbox' class='checkfacture' id ='$cd' value='$cd'>";
            $nestedData[] = $row['id_reclamation'] == null ? "<input type ='checkbox' class='checkfacture' id ='checkfacture' data-id='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='checkfacture' data-id='$cd'>";
            $nestedData[] = $row['code'];
            $nestedData[] = $row['refDocAsso'];
            $nestedData[] = "<div style='text-align:right !important; margin-right:5px !important'>".number_format($row['montant'], 2, ',', ' ')."</div>";
            $nestedData[] = $row['datefacture'];
            $nestedData[] = $row['dateDocAsso'];

            

            $row['statut_reclamation_id'] != null ? $nestedData[] = $this->em->getRepository(Statut::class)->find($row['statut_reclamation_id'])->getDesignation() : $nestedData[] = "";
            // dd('hi');


            $nestedData[] = $row['id_reclamation'] == null ? '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="ugouv" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="ugouv" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>' : '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"  style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="ugouv" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="ugouv" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';            

                $reclamation = null;
                
                if($row['id_reclamation']){
                    $reclamation = $this->em->getRepository(Reclamation::class)->find($row['id_reclamation']);
                }
                // dd($reclamation[0]->getReponses());

                if($row['id_reclamation'] != null && ($reclamation and count($reclamation->getReponses()) == 0)){
                    $etat_bg ="etat_bg_disable";
                }else if($row['id_reclamation'] != null && ($reclamation and $reclamation->getReponses())){
                    $etat_bg ="etat_bg_blue";
                }else{
                    $etat_bg = "";
                }

                
                if($row['id_reclamation'] == null && $row['UPPER(o.id)'] != null && $row['executer'] == 1){
                    // dd('hi');
                    $etat_bg ="etat_bg_vert";
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

    #[Route('/list2', name: 'app_fournisseur_factures_list2')]
    public function list2(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where f.userCreated_id = " .$this->getUser()->getId()." and f.active = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'f.id','dt' => 0),
            array( 'db' => 'f.numFacture','dt' => 1),
            array( 'db' => 'f.montant','dt' => 2),
            array( 'db' => 'f.observation','dt' => 3),
            array( 'db' => 'f.datefacture','dt' => 4),
            array( 'db' => 'f.reclamation_id','dt' => 5),
            array( 'db' => 'st.designation','dt' => 6),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM facture f
        left join statut st on st.id = f.statut_id
        
        $filtre "
        ;
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
                
            if($row['reclamation_id']){
                $reclamation = $this->em->getRepository(Reclamation::class)->find($row['reclamation_id']);
            }
            // dd($reclamation[0]->getReponses());
            $nestedData[] = $row['reclamation_id'] == null ?"<input type ='checkbox' class='checkfacture' id ='$cd' value='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='$cd' value='$cd'>";
            $nestedData[] = $row['numFacture'];
            $nestedData[] = $row['montant'];
            $nestedData[] = $row['observation'];
            $nestedData[] = $row['datefacture'];
            $nestedData[] = $row['designation']; //statut designation
            $nestedData[] = $row['reclamation_id'] == null ? '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="local" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>' : '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"  style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="local" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';
            
            
            if($row['reclamation_id'] != null && ($reclamation and count($reclamation->getReponses()) == 0)){
                $etat_bg ="etat_bg_disable";
            }else if($row['reclamation_id'] != null && ($reclamation and $reclamation->getReponses())){
                $etat_bg ="etat_bg_blue";
            }else{
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
    public function details(ManagerRegistry $doctrine,$factureCab, $type): Response
    {
        $entityManager = $doctrine->getManager('ugouv')->getConnection();

        if($type == "local"){
            $facture = $this->em->getRepository(Facture::class)->find($factureCab);
            $reclamation = $facture->getReclamation();

            if($reclamation){
                
                $factures_infos = $this->render("fournisseur/factures/pages/detailsLocal.html.twig", [
                    'facture' => $facture,
                    'reclamation' => $reclamation,
                ])->getContent();
    
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }else{
                return new JsonResponse('Y\'a aucune reclamation a cette facture!',500);
            }

        }else{
            $query = "SELECT cab.id_reclamation, cab.montant as montant, cab.datefacture as datefacture, cab.observation,  ar.titre as article, u.designation as unite, det.quantite, det.prixunitaire , det.tva FROM `ua_t_facturefrsdet` det
            INNER JOIN ua_t_facturefrscab cab on cab.id = det.ua_t_facturefrscab_id
            INNER JOIN uarticle ar on ar.id = det.u_article_id
            INNER JOIN p_unite u on u.id = det.p_unite_id where cab.id =" . $factureCab;
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $dets = $result->fetchAll();
            $reclamation = $this->em->getRepository(Reclamation::class)->findby(['id'=> $dets[0]['id_reclamation']]);
            if($reclamation){
                 // dd($reclamation);
                $factures_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
                    'dets' => $dets[0],
                    'reclamation' => $reclamation[0],
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }else{
                $factures_infos = $this->render("fournisseur/factures/pages/detailsUgouv.html.twig", [
                    'dets' => $dets[0],
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }
           
        }

    }

    #[Route('/reclamation/{factureCab}/{type}', name: 'app_fournisseur_factures_reclamation')]
    public function reclamation(ManagerRegistry $doctrine,$factureCab, $type): Response
    {
        $entityManager = $doctrine->getManager('ugouv')->getConnection();

        if($type == "local"){
            $facture = $this->em->getRepository(Facture::class)->find($factureCab);
            $reclamation = $facture->getReclamation();
            if($reclamation){

                $factures_infos = $this->render("fournisseur/factures/pages/detailsReclamation.html.twig", [
                    'facture' => $facture,
                    'reclamation' => $reclamation,
                    'rec'=> true
                ])->getContent();
    
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }else{
                return new JsonResponse('Y\'a aucune reclamation a cette facture!',500);
            }

        }else{
            $query = "SELECT cab.id_reclamation, cab.montant as montant, cab.datefacture as datefacture, cab.observation,  ar.titre as article, u.designation as unite, det.quantite, det.prixunitaire , det.tva FROM `ua_t_facturefrsdet` det
            INNER JOIN ua_t_facturefrscab cab on cab.id = det.ua_t_facturefrscab_id
            INNER JOIN uarticle ar on ar.id = det.u_article_id
            INNER JOIN p_unite u on u.id = det.p_unite_id where cab.id =" . $factureCab;
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $dets = $result->fetchAll();
            $reclamation = $this->em->getRepository(Reclamation::class)->findby(['id'=> $dets[0]['id_reclamation']]);

            if($reclamation){

                // dd($reclamation);
                $factures_infos = $this->render("fournisseur/factures/pages/detailsReclamation.html.twig", [
                    'dets' => $dets[0],
                    'reclamation' => $reclamation[0],
                    'rec'=> true
                ])->getContent();
                // dd($dets);
                return new JsonResponse([
                    'infos' => $factures_infos
                ]);
            }else{
                return new JsonResponse('Y\'a aucune reclamation a cette facture!',500);
            }
        }

    }

    #[Route('/reclamer', name: 'app_fournisseur_factures_reclamer')]
    public function ajouter(Request $request, ManagerRegistry $doctrine): Response
    {
        if($request->get("observation") && $request->get("objet") ){
            $factures = array_unique(json_decode($request->get("factures")));
            // dd(!$factures);
            // dd('hi');
            $reclamation = new Reclamation();

            $reclamation->setObservation($request->get("observation"));
            $reclamation->setObjet($request->get("objet"));
            

            $reclamation->setUserCreated($this->getUser());
            $reclamation->setCreated(new \DateTime());

            $this->em->persist($reclamation);

            $this->em->flush();
            if($factures){
                foreach($factures as $facture){
                    // dd($facture);
                    $entityManager = $doctrine->getManager('ugouv')->getConnection();
                    $query = "UPDATE ua_t_facturefrscab SET id_reclamation = " . $reclamation->getId().", statut_reclamation_id = 2 where id = " .$facture;
                    $statement = $entityManager->prepare($query);
                    $result = $statement->executeQuery();
                    $fournisseurs = $result->fetchAll();
                }
            }
            

            return new JsonResponse('Votre reclamation a bien envoyer!',200);
        }else{
            return new JsonResponse('vous devez remplir tous les champs!',500);
        }
    }

    #[Route('/message', name: 'app_fournisseur_factures_message')]
    public function repondre(Request $request, ManagerRegistry $doctrine): Response
    {
        // dd($request);
        if($request->get("message")){
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
        }else{
            return new JsonResponse('vous devez remplir tous les champs!',500);
        }
    }

    #[Route('/ajouter', name: 'app_fournisseur_factures_ajouter')]
    public function ajouterFactures(Request $request, ManagerRegistry $doctrine): Response
    {
        $factures = json_decode($request->get("factures"));
        // dd($factures);
        if($request->get("objet") && $request->get("observation")){
            
            
            $reclamation = new Reclamation();

            $reclamation->setObservation($request->get("observation"));
            $reclamation->setObjet($request->get("objet"));
            

            $reclamation->setUserCreated($this->getUser());
            $reclamation->setCreated(new \DateTime());

            $this->em->persist($reclamation);
            $statut = $this->em->getRepository(Statut::class)->find(2);
            // dd($statut);

            foreach ($factures as $fac) {
                $facture = new Facture();
                $facture->setNumFacture($fac->numFacture);
                $facture->setMontant($fac->montant);
                $facture->setDateFacture(new \DateTime($fac->date));
                $facture->setObservation($fac->observation);
                $facture->setCreated(new \DateTime());
                $facture->setUserCreated($this->getUser());
                $facture->setReclamation($reclamation);
                $facture->setStatut($statut);
                $this->em->persist($facture);

            }

            $this->em->flush();
            

            return new JsonResponse('Les factures sont bien envoyer!',200);
        }else{
            return new JsonResponse('vous devez remplir tous les champs!',500);
        }
    }
}
