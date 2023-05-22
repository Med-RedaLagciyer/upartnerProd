<?php

namespace App\Controller\Fournisseur;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/fournisseur/reclamations')]
class ReclamationsController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_fournisseur_reclamations')]
    public function index(): Response
    {
        return $this->render('fournisseur/reclamations/index.html.twig');
    }

    #[Route('/list', name: 'app_fournisseur_reclamations_list')]
    public function list(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where active = 1 and r.userCreated_id = ".$this->getUser()->getId()." and rep.id is null";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'r.id','dt' => 0),
            array( 'db' => 'r.objet','dt' => 1),
            array( 'db' => 'r.observation','dt' => 2),
            array( 'db' => 'r.created','dt' => 3)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r LEFT JOIN reponse rep on rep.reclamation_id = r.id
        
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
            // dd($row);
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 0) {
                    if($key == 2){
                        // if(strlen($value) > 50){
                        //     $value = substr($value, 0, 50) . "...";
                        // }
                        $nestedData[] = "<div class='text-truncate' title='".$value."' style='text-align:left !important'><b >" .$row["objet"]. "</b><br>".$value."</div>";
                    }else{
                        $nestedData[] = $value;
                        $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 7rem !important; min-width:unset !important"><a id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a id="btnModifier" class="dropdown-item btn-xs"><i class="fas fa-pen mr-2"></i>Modifier</a><a id="btnSupprimer" class="dropdown-item btn-xs"><i class="fas fa-times-circle mr-2"></i> Supprimer</a>' ;
                    }
                }
                
               
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = "";
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

    #[Route('/listreponses', name: 'app_fournisseur_reclamations_list_reponses')]
    public function list2(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where active = 1 and rep.admin = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'r.id','dt' => 0),
            array( 'db' => 'r.objet','dt' => 1),
            array( 'db' => 'r.observation','dt' => 2),
            array( 'db' => 'rep.message','dt' => 2),
            array( 'db' => 'r.created','dt' => 3),
            array( 'db' => 'rep.userSeen','dt' => 4)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r INNER JOIN reponse rep on rep.reclamation_id = r.id
        
        
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
            // dd($row);
            // $nestedData[] = "<input type ='checkbox' class='checkreclamation' id ='$cd' value='$cd'>";
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 0 and $key != 5) {
                    if($key == 2){
                        // if(strlen($value) > 50){
                        //     $value = substr($value, 0, 50) . "...";
                        // }
                        $nestedData[] = "<div class='text-truncate' title='".$value."' style='text-align:left !important'><b >" .$row["objet"]. "</b><br>".$value."</div>";
                    }else{
                        $nestedData[] = $value;
                        
                    }
                }
                // dd($row);
                
                $seen_bg = $row['userSeen'] == 1 ? "seen_bg" : "unseen_bg";
            }
            $nestedData[] = '<button id="btnReponse" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i></button>' ;
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $seen_bg;
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

    #[Route('/details/{reclamation}', name: 'app_fournisseur_reclamations_details')]
    public function details(ManagerRegistry $doctrine,Reclamation $reclamation, Request $request): Response
    {
        // dd();
        
        
        $entityManager = $doctrine->getManager('ugouv')->getConnection();

        $query = "SELECT cab.* FROM `ua_t_facturefrscab` cab where id_reclamation =" . $reclamation->getId();
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $factures = $result->fetchAll();
        // dd($factures);
        $reclamation_infos = $this->render("fournisseur/reclamations/pages/infos_reclamation.html.twig", [
            'factures' => $factures,
            'reclamation' => $reclamation,
        ])->getContent();
        $reclamation_modification = $this->render("fournisseur/reclamations/pages/modification_reclamation.html.twig", [
            'factures' => $factures,
            'reclamation' => $reclamation,
        ])->getContent();
        $reclamation_reponse = false;
        if($request->get('reponse')){
            $reponse = $this->em->getRepository(Reponse::class)->findBy(['reclamation' => $reclamation]);
            $reponse[0]->setUserSeen(1);
            $this->em->flush();
            $reclamation_reponse = $this->render("fournisseur/reclamations/pages/reponse_reclamation.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
            ])->getContent();
        }
        return new JsonResponse([
            'infos' => $reclamation_infos,
            'modification' => $reclamation_modification,
            'reclamation_reponse' => $reclamation_reponse
        ]);
    }

    #[Route('/delete', name: 'app_fournisseur_reclamations_delete')]
    public function delete(ManagerRegistry $doctrine, Request $request): Response
    {
        $recs = array_unique(json_decode($request->get("reclamations")));
        $reclamations = $this->em->getRepository(Reclamation::class)->findby(['id' => $recs]);
        // dd('hi');
        // dd($reclamations);
        foreach ($reclamations as $rec) {
            $rec->setActive(0);
            $this->em->flush();

            $entityManager = $doctrine->getManager('ugouv')->getConnection();

            $query = "UPDATE ua_t_facturefrscab cab SET id_reclamation = null where id_reclamation =" . $rec->getId();
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
        }
        
        
        return new JsonResponse('Reclamations sont bien supprimer!',200);
    }

    #[Route('/modifier/{reclamation}', name: 'app_fournisseur_reclamations_modifier')]
    public function modifier(ManagerRegistry $doctrine, Request $request, Reclamation $reclamation): Response
    {
        // dd($request->get('objet'));
        if($request->get("observation") && $request->get("objet") ){
            $reclamation->setObjet($request->get('objet'));
            $reclamation->setObservation($request->get('observation'));
            $reclamation->setUserUpdated($this->getUser());
            $reclamation->setUpdated(new \DateTime());
            $this->em->flush();
            
            return new JsonResponse('Reclamation a bien modifier!',200);
        }else{
            return new JsonResponse('vous devez remplisser tous les champs!',500);
        }
        
    }

    #[Route('/message', name: 'app_fournisseur_reclamation_message')]
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
}
