<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\PartenaireValide;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
    public function list(ManagerRegistry $doctrine, Request $request): Response
    {

        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 and active";
        // dd($params->all('columns')[0]);

        $columns = array(
            array('db' => 'p.id', 'dt' => 0),
            array('db' => 'p.code', 'dt' => 1),
            array('db' => 'p.nom', 'dt' => 2),
            array('db' => 'p.prenom', 'dt' => 3),
            array('db' => 'p.ice_o', 'dt' => 4),
            array('db' => 'p.societe', 'dt' => 5),
            array('db' => 'p.ice', 'dt' => 6),
            array('db' => 'p.rib', 'dt' => 7),

        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM u_p_partenaire p 
        
        $filtre ";
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sql);
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
        $stmt = $doctrine->getmanager('default')->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();


        $data = array();
        // dd($result);
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            $nestedData[] = $row['code'];
            // $nestedData[] = $row['nom'];
            // $nestedData[] = $row['prenom'];
            $nestedData[] = $row['societe'];
            $nestedData[] = $row['ice'];
            $nestedData[] = $row['rib'];


            $nestedData[] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false" ><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right" style="width: 8rem !important; min-width:unset !important; font-size : 12px !important;"><a  id="btnDetails" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Details</a><a id="btnModification" class="dropdown-item btn-xs"><i class="fas fa-eye mr-2"></i> Modifier</a>';


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


    #[Route('/details/{frs}', name: 'app_admin_fournisseurs_details')]
    public function details(ManagerRegistry $doctrine, $frs): Response
    {
        // dd($frs);
        $entityManager = $doctrine->getManager('default')->getConnection();


        $query = "SELECT id, nom, prenom, societe, ice, ice_o, mail1, mail2, tel1, tel2, adresse, pays, ville FROM `u_p_partenaire` where id =" . $frs;
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $dets = $result->fetchAll();


        $frs_infos = $this->render("admin/fournisseurs/pages/details.html.twig", [
            'frs' => $dets[0],
        ])->getContent();
        $frs_modif = $this->render("admin/fournisseurs/pages/modification.html.twig", [
            'frs' => $dets[0],
        ])->getContent();
        return new JsonResponse([
            'infos' => $frs_infos,
            'modif' => $frs_modif,
        ]);
    }

    #[Route('/modifier', name: 'app_admin_fournisseurs_modifier')]
    public function modifier(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response
    {
        // dd($request);
        if ($request->get("nom") && $request->get("prenom") && $request->get("ice_o")) {
            if (strlen($request->get('ice_o')) !== 15) {
                return new JsonResponse('ICE DOIT AVOIR PLUS DE 15 CARACTÈRES', 500);
            }
            // dd($request->get("ice_o"));
            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "Update `u_p_partenaire` set societe = '" . $request->get("societe") . "', ice = '" . $request->get("ice_o") . "', nom = '" . $request->get("nom") . "', prenom = '" . $request->get("prenom") . "', tel1 = '" . $request->get("tel1") . "', tel2 = '" . $request->get("tel2") . "', mail1 = '" . $request->get("mail1") . "', mail2 = '" . $request->get("mail2") . "', pays = '" . $request->get("pays") . "', ville = '" . $request->get("ville") . "', adresse = '" . $request->get("adresse") . "', ice_o = '" . $request->get("ice_o") . "' where id =" . $request->get("idfrs");
            // dd($query);
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();

            // $partenaire = $this->em->getRepository(PartenaireValide::class)->findby(["partenaireId" => $request->get("idfrs")]);
            // dd(!$partenaire);
            $message = "MISE À JOUR TERMINÉE AVEC SUCCÈS";
            $user = $this->em->getRepository(User::class)->findby(['partenaireId' => $request->get("idfrs")]);

            $defaultPassword = '0123456789';
            if (!$user) {
                // dd($request->get("idfrs"));
                $user = new User();
                $user->setUsername($request->get("ice_o"));
                $user->setNom($request->get("nom"));
                $user->setPrenom($request->get("prenom"));
                $user->setPartenaireId($request->get("idfrs"));
                $user->setPassword($passwordHasher->hashPassword(
                    $user,
                    $defaultPassword
                ));
                $user->setRoles(["ROLE_FRS"]);

                $this->em->persist($user);
                $this->em->flush();
                $message .= " ET LE COMPTE EST CRÉE.";
            }
            // else {
            //     $user->setUsername($request->get("ice_o"));
            //     $user->setPassword($passwordHasher->hashPassword(
            //         $user,
            //         $defaultPassword
            //     ));
            //     $this->em->flush();
            // }

            // if (!$partenaire) {
            //     $partenaire = new PartenaireValide();

            //     $partenaire->setPartenaireId(intval($request->get("idfrs")));

            //     $partenaire->setSociete($request->get("societe"));
            //     $partenaire->setICE($request->get("ice"));
            //     $partenaire->setNom($request->get("nom"));
            //     $partenaire->setPrenom($request->get("prenom"));
            //     $partenaire->setTel1($request->get("tel1"));
            //     $partenaire->setContact1($request->get("contact1"));
            //     $partenaire->setTel2($request->get("tel2"));
            //     $partenaire->setContact2($request->get("contact2"));
            //     $partenaire->setMail1($request->get("mail1"));
            //     $partenaire->setPays($request->get("pays"));
            //     $partenaire->setVille($request->get("ville"));
            //     $partenaire->setAdresse($request->get("adresse"));
            //     $partenaire->setIceO($request->get("ice_o"));


            //     $partenaire->setUserCreated($this->getUser());
            //     $partenaire->setCreated(new \DateTime());

            //     $this->em->persist($partenaire);

            //     $this->em->flush();
            //     $user = $this->em->getRepository(User::class)->findby(['username' => $request->get("ice_o")]);

            //     $defaultPassword = '0123456789';
            //     if (!$user) {
            //         $user = new User();
            //         $user->setUsername($request->get("ice_o"));
            //         $user->setNom($request->get("nom"));
            //         $user->setPrenom($request->get("prenom"));
            //         $user->setPassword($passwordHasher->hashPassword(
            //             $user,
            //             $defaultPassword
            //         ));
            //         $user->setRoles(["ROLE_FRS"]);

            //         $this->em->persist($user);
            //         $this->em->flush();
            //         $message .= " ET LE COMPTE EST CRÉE.";
            //     } else {
            //         $user->setUsername($request->get("ice_o"));
            //         $user->setPassword($passwordHasher->hashPassword(
            //             $user,
            //             $defaultPassword
            //         ));
            //         $this->em->flush();
            //     }
            // } else {
            //     $PartenaireValide = $this->em->getRepository(PartenaireValide::class)->find($partenaire[0]->getId());
            //     // dd($PartenaireValide);
            //     $PartenaireValide->setPartenaireId(intval($request->get("idfrs")));

            //     if ($request->get("societe") != "") $PartenaireValide->setSociete($request->get("societe"));
            //     $PartenaireValide->setNom($request->get("nom"));
            //     $PartenaireValide->setPrenom($request->get("prenom"));
            //     if ($request->get("tel1") != "") $PartenaireValide->setTel1($request->get("tel1"));
            //     if ($request->get("contact1") != "") $PartenaireValide->setContact1($request->get("contact1"));
            //     if ($request->get("tel2") != "") $PartenaireValide->setTel2($request->get("tel2"));
            //     if ($request->get("contact2") != "") $PartenaireValide->setContact2($request->get("contact2"));
            //     if ($request->get("mail1") != "") $PartenaireValide->setMail1($request->get("mail1"));
            //     if ($request->get("pays") != "") $PartenaireValide->setPays($request->get("pays"));
            //     if ($request->get("ville") != "") $PartenaireValide->setVille($request->get("ville"));
            //     if ($request->get("adresse") != "") $PartenaireValide->setAdresse($request->get("adresse"));
            //     $PartenaireValide->setIceO($request->get("ice_o"));
            //     $PartenaireValide->setUserUpdated($this->getUser());
            //     $PartenaireValide->setUpdated(new \DateTime());
            //     $this->em->flush();

            //     $message = "MISE À JOUR TERMINÉE AVEC SUCCÈS";

            //     $user = $this->em->getRepository(User::class)->findby(['username' => $request->get("ice_o")]);

            //     $defaultPassword = '0123456789';
            //     if (!$user) {
            //         $user = new User();
            //         $user->setUsername($request->get("ice_o"));
            //         $user->setNom($request->get("nom"));
            //         $user->setPrenom($request->get("prenom"));
            //         $user->setPassword($passwordHasher->hashPassword(
            //             $user,
            //             $defaultPassword
            //         ));
            //         $user->setRoles(["ROLE_FRS"]);

            //         $this->em->persist($user);
            //         $this->em->flush();
            //         $message .= " ET LE COMPTE EST CRÉE.";
            //     } else {
            //         $user->setUsername($request->get("ice_o"));
            //         $user->setPassword($passwordHasher->hashPassword(
            //             $user,
            //             $defaultPassword
            //         ));
            //         $this->em->flush();
            //     }
            // }



            return new JsonResponse($message, 200);
        } else {
            return new JsonResponse('CHAMP ICE_O OBLIGATOIRE', 500);
        }
    }
}
