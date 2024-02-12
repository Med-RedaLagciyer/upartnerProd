<?php

namespace App\Controller\Fournisseur;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Controller\DatatablesController;
use App\Entity\Facture;
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
    public function list(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where active = 1 and r.userCreated_id = " . $this->getUser()->getId() . " ";
        // dd($params->all('columns')[0]);

        $columns = array(
            array('db' => 'r.id', 'dt' => 0),
            array('db' => 'r.objet', 'dt' => 1),
            array('db' => 'r.observation', 'dt' => 2),
            array('db' => 'rep.message', 'dt' => 3),
            array('db' => 'r.created', 'dt' => 4),
            array('db' => 'rep.userSeen', 'dt' => 5)


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
            $reponses = $this->em->getRepository(Reponse::class)->findby(['reclamation' => $row['id']], ["id" => "desc"]);
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 0 and $key != 5) {
                    if ($key == 2) {
                        // if(strlen($value) > 50){
                        //     $value = substr($value, 0, 50) . "...";
                        // }
                        $nestedData[] = "<div class='text-truncate' title='" . $value . "' style='text-align:left !important'><b >" . $row["objet"] . "</b><br>" . $value . "</div>";
                    } elseif ($key == 3) {
                        if ($reponses && $reponses[0]->getUserCreated() != $this->getUser()) {
                            $etat_bg = "etat_bg_blue";
                            $nestedData[] = "<div class='text-truncate' title='" . $reponses[0]->getMessage() . "' style='text-align:left !important'>" . $reponses[0]->getMessage() . "</div>";
                            $nestedData[] = "A répondu";
                        } else {
                            $etat_bg = "etat_bg_disable";
                            $nestedData[] = "";
                            $nestedData[] = "En attente de réponse";
                        }
                    } else {
                        $nestedData[] = $value;
                        $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" style="color: black;"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 7rem !important; min-width:unset !important"><a id="btnReponse" class="dropdown-item btn-xs"><i class="fas fa-comment mr-2"></i>Reponse</a><a id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a id="btnModifier" class="dropdown-item btn-xs"><i class="fas fa-pen mr-2"></i>Modifier</a><a id="btnSupprimer" class="dropdown-item btn-xs"><i class="fas fa-times-circle mr-2"></i> Supprimer</a>';
                    }
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

    #[Route('/listreponses', name: 'app_fournisseur_reclamations_list_reponses')]
    public function list2(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $code = $this->getUser()->getUsername();
        // dd($code);

        $filtre = "where active = 1 and rep.admin = 1";
        // dd($params->all('columns')[0]);

        $columns = array(
            array('db' => 'r.id', 'dt' => 0),
            array('db' => 'r.objet', 'dt' => 1),
            array('db' => 'r.observation', 'dt' => 2),
            array('db' => 'rep.message', 'dt' => 3),
            array('db' => 'r.created', 'dt' => 4),
            array('db' => 'rep.userSeen', 'dt' => 5)

        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM reclamation r INNER JOIN reponse rep on rep.reclamation_id = r.id
        
        
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
            // dd($row);
            // $nestedData[] = "<input type ='checkbox' class='checkreclamation' id ='$cd' value='$cd'>";
            foreach (array_values($row) as $key => $value) {

                if ($key != 1 and $key != 0 and $key != 5) {
                    if ($key == 2) {
                        // if(strlen($value) > 50){
                        //     $value = substr($value, 0, 50) . "...";
                        // }
                        $nestedData[] = "<div class='text-truncate' title='" . $value . "' style='text-align:left !important'><b >" . $row["objet"] . "</b><br>" . $value . "</div>";
                    } elseif ($key == 3) {
                        $nestedData[] = "<div class='text-truncate' title='" . $value . "' style='text-align:left !important'><b >" . $value . "</b><br>" . $value . "</div>";
                    } else {
                        $nestedData[] = $value;
                    }
                }
                // dd($row);

                $seen_bg = $row['userSeen'] == 1 ? "seen_bg" : "unseen_bg";
            }
            $nestedData[] = '<button id="btnReponse" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i></button>';
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
    public function details(ManagerRegistry $doctrine, Reclamation $reclamation, Request $request): Response
    {
        // dd($request->get('reponse') == "yes");

        $reclamation->setAdminSeen(0);
        $this->em->flush();
        // dd(count($reclamation->getFactures()));

        $entityManager = $doctrine->getManager('default')->getConnection();
        // dd('tt');
        // dd($reclamation);

        if (count($reclamation->getFactures()) > 0) {
            $factures = $this->em->getRepository(Facture::class)->findby(['reclamation' => $reclamation->getId(), 'active' => 1]);
            $reclamation_infos = $this->render("fournisseur/reclamations/pages/infos_reclamation.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();

            $reclamation_repondre = $this->render("fournisseur/reclamations/pages/reponse_reclamation.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();

            $reclamation_modification = $this->render("fournisseur/reclamations/pages/modification_reclamation.html.twig", [
                'factures' => $factures,
                'reclamation' => $reclamation,
                'local' => true
            ])->getContent();

            if ($request->get('reponse')) {
                // dd("hi");
                $reponse = $this->em->getRepository(Reponse::class)->findBy(['reclamation' => $reclamation]);
                if ($reponse) {
                    $reponse[0]->setUserSeen(1);
                    $this->em->flush();
                }
            }
            // dd($reclamation);
            return new JsonResponse([
                'infos' => $reclamation_infos,
                'reclamation_reponse' => $reclamation_repondre,
                'modification' => $reclamation_modification,
                'local' => true
            ]);
        } else {
            $query = "SELECT id, code, datecommande, refDocAsso FROM `ua_t_commandefrscab` WHERE id_reclamation = " . $reclamation->getId() . ";";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $commande = $result->fetchAll();
            // dd($factures);
            $reclamation_infos = $this->render("fournisseur/reclamations/pages/infos_reclamation.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false
            ])->getContent();

            $reclamation_repondre = $this->render("fournisseur/reclamations/pages/reponse_reclamation.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false

            ])->getContent();
            $reclamation_modification = $this->render("fournisseur/reclamations/pages/modification_reclamation.html.twig", [
                'commande' => $commande,
                'reclamation' => $reclamation,
                'local' => false
            ])->getContent();

            if ($request->get('reponse')) {
                $reponse = $this->em->getRepository(Reponse::class)->findBy(['reclamation' => $reclamation]);
                if ($reponse) {
                    $reponse[0]->setUserSeen(1);
                    $this->em->flush();
                }
            }
            // dd($reclamation);
            return new JsonResponse([
                'infos' => $reclamation_infos,
                'reclamation_reponse' => $reclamation_repondre,
                'modification' => $reclamation_modification,
                'local' => false
            ]);
        }


        // $entityManager = $doctrine->getManager('default')->getConnection();

        // $query = "SELECT cab.* FROM `ua_t_facturefrscab` cab where id_reclamation =" . $reclamation->getId();
        // $statement = $entityManager->prepare($query);
        // $result = $statement->executeQuery();
        // $factures = $result->fetchAll();
        // // dd($factures);
        // $reclamation_infos = $this->render("fournisseur/reclamations/pages/infos_reclamation.html.twig", [
        //     'factures' => $factures,
        //     'reclamation' => $reclamation,
        // ])->getContent();
        // $reclamation_modification = $this->render("fournisseur/reclamations/pages/modification_reclamation.html.twig", [
        //     'factures' => $factures,
        //     'reclamation' => $reclamation,
        // ])->getContent();
        // $reclamation_reponse = false;
        // if ($request->get('reponse')) {
        //     $reponse = $this->em->getRepository(Reponse::class)->findBy(['reclamation' => $reclamation]);
        //     $reponse[0]->setUserSeen(1);
        //     $this->em->flush();
        //     $reclamation_reponse = $this->render("fournisseur/reclamations/pages/reponse_reclamation.html.twig", [
        //         'factures' => $factures,
        //         'reclamation' => $reclamation,
        //     ])->getContent();
        // }
        // return new JsonResponse([
        //     'infos' => $reclamation_infos,
        //     'modification' => $reclamation_modification,
        //     'reclamation_reponse' => $reclamation_reponse
        // ]);
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

            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "UPDATE ua_t_facturefrscab cab SET id_reclamation = null where id_reclamation =" . $rec->getId();
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
        }


        return new JsonResponse('SUPPRESSION TERMINÉE AVEC SUCCÈS', 200);
    }

    #[Route('/deleteFacture', name: 'app_fournisseur_reclamations_deleteFacture')]
    public function deleteFacture(ManagerRegistry $doctrine, Request $request): Response
    {
        $idFacture = $request->request->get('id');
        $facture = $this->em->getRepository(Facture::class)->find($idFacture);
        // dd($facture);
        $facture->setActive(0);
        $this->em->flush();

        return new JsonResponse('SUPPRESSION TERMINÉE AVEC SUCCÈS', 200);
    }

    #[Route('/modifier/{reclamation}', name: 'app_fournisseur_reclamations_modifier')]
    public function modifier(ManagerRegistry $doctrine, Request $request, Reclamation $reclamation): Response
    {
        // dd($request);
        if ($request->get("observation") && $request->get("objet")) {
            $reclamation->setObjet($request->get('objet'));
            $reclamation->setObservation($request->get('observation'));
            $reclamation->setUserUpdated($this->getUser());
            $reclamation->setUpdated(new \DateTime());
            $this->em->flush();

            return new JsonResponse('MISE À JOUR TERMINÉE AVEC SUCCÈS', 200);
        } else {
            return new JsonResponse('CHAMPS OBLIGATOIRES', 500);
        }
    }

    #[Route('/message', name: 'app_fournisseur_reclamation_message')]
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
}
