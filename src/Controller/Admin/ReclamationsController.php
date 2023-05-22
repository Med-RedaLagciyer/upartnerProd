<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Controller\DatatablesController;
use App\Entity\Reponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/admin/reclamations')]
class ReclamationsController extends AbstractController
{

    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_admin_reclamations')]
    public function index(): Response
    {
        return $this->render('admin/reclamations/index.html.twig');
    }

    #[Route('/list', name: 'app_admin_reclamations_list')]
    public function list(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where r.active = 1 and ( rep.id is null or rep.admin != 1 )";   
        // dd($params->all('columns')[0]);

        if (!empty($params->all('columns')[0]['search']['value'])) {
            if($params->all('columns')[0]['search']['value'] == "Oui" ){
                $filtre = "where r.active = 1 and rep.admin = 1 ";   
            }else{
                $filtre = "where r.active = 1 and ( rep.id is null or rep.admin != 1 )";   
            }
        }
            
        $columns = array(
            array( 'db' => 'r.id','dt' => 0),
            array( 'db' => 'r.objet','dt' => 1),
            array( 'db' => 'r.observation','dt' => 2),
            array( 'db' => 'r.created','dt' => 3),
            array( 'db' => 'u.username','dt' => 4),
            array( 'db' => 'r.adminSeen','dt' => 5)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r LEFT JOIN reponse rep on rep.reclamation_id = r.id LEFT JOIN user u on u.id = r.userCreated_id
        
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
        $seen_bg = "";
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            // $nestedData[] = "<input type ='checkbox' class='checkreclamation' id ='$cd' value='$cd'>";
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 5) {
                    if($key == 2){
                        $nestedData[] = "<div class='text-truncate' title='".$value."' style='text-align:left !important'><b >" .$row["objet"]. "</b><br>".$value."</div>";
                    }
                    else{
                        $nestedData[] = $value;
                        
                    }
                }
                
                // dd($row['adminSeen']);
                
                $seen_bg = $row['adminSeen'] == 1 ? "seen_bg" : "unseen_bg";
            }
            $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a id="btnRepondre" class="dropdown-item btn-xs"><i class="fas fa-pen mr-2"></i> Repondre</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a></div>' ;

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

    
    #[Route('/details/{reclamation}', name: 'app_admin_reclamations_details')]
    public function details(ManagerRegistry $doctrine,Reclamation $reclamation): Response
    {
        $reclamation->setAdminSeen(1);
        $this->em->flush();

        $entityManager = $doctrine->getManager('ugouv')->getConnection();

        $query = "SELECT cab.* FROM `ua_t_facturefrscab` cab where id_reclamation =" . $reclamation->getId();
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $factures = $result->fetchAll();
        // dd($factures);
        
        $reclamation_infos = $this->render("admin/reclamations/pages/infos_reclamation.html.twig", [
            'factures' => $factures,
            'reclamation' => $reclamation,
        ])->getContent();
        
        $reclamation_repondre = $this->render("admin/reclamations/pages/repondre_reclamation.html.twig", [
            'factures' => $factures,
            'reclamation' => $reclamation,
        ])->getContent();
        // dd($reclamation);
        return new JsonResponse([
            'infos' => $reclamation_infos,
            'repondre' => $reclamation_repondre
        ]);
    }


    

    #[Route('/message', name: 'app_admin_reclamations_message')]
    public function message(Request $request, ManagerRegistry $doctrine): Response
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
            $reponse->setAdmin(true);

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