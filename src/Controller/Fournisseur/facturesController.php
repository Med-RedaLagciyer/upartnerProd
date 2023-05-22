<?php

namespace App\Controller\Fournisseur;

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
    public function index(): Response
    {
        return $this->render('fournisseur/factures/index.html.twig');
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

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM ua_t_facturefrscab f 
        inner join u_p_partenaire p on p.id = f.partenaire_id
        
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
            foreach (array_values($row) as $key => $value) {
                if($key == 6){
                    // $nestedData[] = $value == 1 ? 'oui' : 'non';
                    $nestedData[0] = $value == null ? "<input type ='checkbox' class='checkfacture' id ='checkfacture' data-id='$cd'>" : "<input type ='checkbox' disabled class='checkfacture' id ='checkfacture' data-id='$cd'>";
                    
                    $nestedData[6] = $value == null ? '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="ugouv" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="ugouv" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>' : '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"  style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a data-value="ugouv" id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a data-value="ugouv" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Reclamation</a>';
                }
                else{
                    $nestedData[] = $value;
                }

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
            array( 'db' => 'f.reclamation_id','dt' => 5)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM facture f
        
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
                return new JsonResponse('Y\'a aucune reclamation a cette facture!',500);
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
                    $query = "UPDATE ua_t_facturefrscab SET id_reclamation = " . $reclamation->getId()." where id = " .$facture;
                    $statement = $entityManager->prepare($query);
                    $result = $statement->executeQuery();
                    $fournisseurs = $result->fetchAll();
                }
            }
            

            return new JsonResponse('Votre reclamation a bien envoyer!',200);
        }else{
            return new JsonResponse('vous devez remplisser tous les champs!',500);
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
            return new JsonResponse('vous devez remplisser tous les champs!',500);
        }
    }

    #[Route('/ajouter', name: 'app_fournisseur_factures_ajouter')]
    public function ajouterFactures(Request $request, ManagerRegistry $doctrine): Response
    {
        $factures = json_decode($request->get("factures"));
        // dd($request);
        if($request->get("objet") && $request->get("observation")){
            
            
            $reclamation = new Reclamation();

            $reclamation->setObservation($request->get("observation"));
            $reclamation->setObjet($request->get("objet"));
            

            $reclamation->setUserCreated($this->getUser());
            $reclamation->setCreated(new \DateTime());

            $this->em->persist($reclamation);

            foreach ($factures as $fac) {
                $facture = new Facture();
                $facture->setNumFacture($fac->numFacture);
                $facture->setMontant($fac->montant);
                $facture->setDateFacture(new \DateTime($fac->date));
                $facture->setObservation($fac->observation);
                $facture->setCreated(new \DateTime());
                $facture->setUserCreated($this->getUser());
                $facture->setReclamation($reclamation);
                $this->em->persist($facture);

            }

            $this->em->flush();
            

            return new JsonResponse('Les factures sont bien envoyer!',200);
        }else{
            return new JsonResponse('vous devez remplisser tous les champs!',500);
        }
    }
}