<?php

namespace App\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DatatablesController;

#[Route('/admin/fournisseurs')]
class FournisseursController extends AbstractController
{

    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    
    #[Route('/', name: 'app_admin_fournisseurs')]
    public function index(): Response
    {
        return $this->render('admin/fournisseurs/index.html.twig');
    }

    #[Route('/list', name: 'app_admin_fournisseurs_list')]
    public function list(ManagerRegistry $doctrine,Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'p.id','dt' => 0),
            array( 'db' => 'p.code','dt' => 1),
            array( 'db' => 'p.nom','dt' => 2),
            array( 'db' => 'p.prenom','dt' => 3),
            array( 'db' => 'p.mail1','dt' => 4),
            array( 'db' => 'p.tel1','dt' => 5),
            array( 'db' => 'p.adresse','dt' => 6),
            array( 'db' => 'p.pays','dt' => 7),
            array( 'db' => 'p.ville','dt' => 8),
            array( 'db' => 'p.societe','dt' => 9),
            array( 'db' => 'p.ICE','dt' => 10),

        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM u_p_partenaire p 
        
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
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            
            foreach (array_values($row) as $key => $value) {
                
                    $nestedData[] = $value;
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
}
