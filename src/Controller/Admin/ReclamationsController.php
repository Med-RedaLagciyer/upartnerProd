<?php

namespace App\Controller\Admin;

use App\Entity\Statut;
use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Controller\DatatablesController;
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
    public function list(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where r.active = 1 and ( rep.id is null or rep.admin != 1 )";
        // dd($params->all('columns')[0]);

        if (!empty($params->all('columns')[0]['search']['value'])) {
            if ($params->all('columns')[0]['search']['value'] == "Oui") {
                $filtre = "where r.active = 1 and rep.admin = 1 ";
            } else {
                $filtre = "where r.active = 1 and ( rep.id is null or rep.admin != 1 )";
            }
        }

        $columns = array(
            array('db' => 'r.id', 'dt' => 0),
            array('db' => 'r.objet', 'dt' => 1),
            array('db' => 'r.observation', 'dt' => 2),
            array('db' => 'r.created', 'dt' => 3),
            array('db' => 'u.username', 'dt' => 4),
            array('db' => 'CONCAT( u.nom, " ", u.prenom )', 'dt' => 5),
            array('db' => 'r.adminSeen', 'dt' => 6),

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r LEFT JOIN reponse rep on rep.reclamation_id = r.id LEFT JOIN user u on u.id = r.userCreated_id
        
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
        $seen_bg = "";
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            // $nestedData[] = "<input type ='checkbox' class='checkreclamation' id ='$cd' value='$cd'>";
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 6) {
                    if ($key == 2 ) {
                        $nestedData[] = "<div class='text-truncate' title='" . $value . "' style='text-align:left !important'><b >" . $row["objet"] . "</b><br>" . $value . "</div>";
                    }elseif ($key == 5 ) {
                        $nestedData[] = "<div class='text-truncate-commande' title='" . $value . "' style='text-align:left !important'>" . $value . "</div>";
                    } else {
                        $nestedData[] = $value;
                    }
                }

                // dd($row['adminSeen']);

                $seen_bg = $row['adminSeen'] == 1 ? "seen_bg" : "unseen_bg";
            }
            $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a id="btnRepondre" class="dropdown-item btn-xs"><i class="fas fa-pen mr-2"></i> Repondre</a><a data-value="local" id="btnReclamation" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a></div>';

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
    public function details(ManagerRegistry $doctrine, Reclamation $reclamation): Response
    {
        // dd("hi");
        // $reclamation = $this->em->getRepository(Reclamation::class)->find($reclamation);
        $reclamation->setAdminSeen(0);
        $this->em->flush();
        // dd(count($reclamation->getFactures()));

        $entityManager = $doctrine->getManager('default')->getConnection();
        // dd('tt');
        // dd($reclamation);

        if (count($reclamation->getFactures()) > 0) {
            $factures = $reclamation->getFactures();
            $reclamation_infos = $this->render("admin/reclamations/pages/infos_reclamation.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();

            $reclamation_repondre = $this->render("admin/reclamations/pages/repondre_reclamation.html.twig", [
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
            $query = "SELECT id, code, datecommande, refDocAsso FROM `ua_t_commandefrscab` WHERE id_reclamation = " . $reclamation->getId() . ";";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $commande = $result->fetchAll();
            // dd($factures);
            $reclamation_infos = $this->render("admin/reclamations/pages/infos_reclamation.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false
            ])->getContent();

            $reclamation_repondre = $this->render("admin/reclamations/pages/repondre_reclamation.html.twig", [
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


    #[Route('/dets/{id}/{type}', name: 'app_admin_factures_dets')]
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

        $factures_infos = $this->render("admin/reclamations/pages/dets.html.twig", [
            'dets' => $dets,
        ])->getContent();
        // dd($dets);
        return new JsonResponse([
            'infos' => $factures_infos
        ]);
    }



    #[Route('/message', name: 'app_admin_reclamations_message')]
    public function message(Request $request, ManagerRegistry $doctrine,): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();
        // dd($request);
        if ($request->get("message")) {
            $reclamation = $this->em->getRepository(Reclamation::class)->find($request->get("reclamation"));
            // dd($reclamation);





            $reponse = new Reponse();

            $reponse->setMessage($request->get("message"));
            $reponse->setReclamation($reclamation);


            $reponse->setUserCreated($this->getUser());
            $reponse->setCreated(new \DateTime());
            $reponse->setAdmin(true);

            $this->em->persist($reponse);

            $this->em->flush();

            $statut = $this->em->getRepository(Statut::class)->find(3);

            if ($reclamation->getFactures()) {
                $factures = $reclamation->getFactures();
                if ($factures) {
                    foreach ($factures as $facture) {
                        // dd($facture);
                        $facture->setStatut($statut);

                        $this->em->flush();
                        // dd($facture);

                        // dd('hi');
                    }
                }
            } else {
                $query = "SELECT * FROM `ua_t_facturefrscab` where id_reclamation =" . $reclamation->getId();
                $statement = $entityManager->prepare($query);
                $result = $statement->executeQuery();
                $factures = $result->fetchAll();
                if ($factures) {
                    foreach ($factures as $facture) {
                        $entityManager = $doctrine->getManager('default')->getConnection();
                        $query = "UPDATE ua_t_facturefrscab SET statut_reclamation_id = 3 where id = " . $facture['id'];
                        $statement = $entityManager->prepare($query);
                        $result = $statement->executeQuery();
                        // dd($facture);

                        // dd('hi');
                    }
                }
            }


            return new JsonResponse([
                'message' => $reponse->getMessage(),
                'date' => $reponse->getCreated()->format('d/m/Y'),
            ]);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES.', 500);
        }
    }
}
