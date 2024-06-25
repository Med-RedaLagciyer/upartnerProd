<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\AccessHistorique;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\PartenaireValide;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/admin/users')]
class UsersController extends AbstractController
{
    private $passwordEncoder, $em;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, ManagerRegistry $doctrine)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_admin_users')]
    public function index(): Response
    {
        return $this->render('admin/users/index.html.twig');
    }

    #[Route('/list', name: 'app_admin_users_list')]
    public function list(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where active = 1";
        $statusFilter = $request->query->get('status');
        if ($statusFilter != "") {
            switch ($statusFilter) {
                case 'waiting':
                    $filtre .= " AND valide = 1";
                    break;
                    case 'valid':
                    $filtre .= " AND valide = 2";
                    break;
                case 'unvalid':
                    $filtre .= " AND valide = 0";
                    break;
                case 'all':
                    $filtre .= " AND 1=1";
                    break;
                }
        }
        $columns = array(
            array('db' => 'u.id', 'dt' => 0),
            array('db' => 'u.username', 'dt' => 1),
            array('db' => 'u.nom', 'dt' => 2),
            array('db' => 'u.prenom', 'dt' => 3),
            array('db' => 'u.roles', 'dt' => 4),
            array('db' => 'u.valide', 'dt' => 5),

        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM user u 
        $filtre ";

        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
 
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= DatatablesController::Order($request, $columns);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();

        $data = array();

        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];

            foreach (array_values($row) as $key => $value) {
                if ($key == 5) {
                    $nestedData[] = $value == 2 ?  "<i class='fa fa-check-square text-success' id='$cd'></i>" : "<i class='fa fa-times-circle text-danger' id='$cd'></i>";
                    $nestedData[] = "<button class='btn_reinitialiser btn btn-secondary' id='$cd'><i class='fas fa-sync'></i></button>";
                } else if ($key == 4) {
                    $nestedData[] = $value == '["ROLE_ADMIN"]' ?  "ADMIN" : "PARTENAIRE";
                } else {
                    $nestedData[] = $value;
                }
            }
            $nestedData[6] = '<a class="" data-toggle="dropdown" href="#" aria-expanded="false"><i class="fa fa-ellipsis-v" style ="color: #000;"></i></a><div class="dropdown-menu dropdown-menu-right"><a href="#" id="btnDevalider" class="dropdown-item btn-xs"><i class="fas fa-times-circle mr-2"></i> Dévalider/Valider</a><a href="#" class="dropdown-item btn-xs" id="btnReinitialiser"><i class="fas fa-sync mr-2"></i> Reinitialiser</a>';
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = "";
            $data[] = $nestedData;
            $i++;
        }
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        );
        return new Response(json_encode($json_data));
    }

    #[Route('/fournisseurs', name: 'app_admin_users_fournisseurs')]
    public function fournisseurs(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();
        $query = "SELECT  id, ice_o , nom, prenom from u_p_partenaire Where active = 1";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $fournisseurs = $result->fetchAll();

        foreach ($fournisseurs as $key => $frs) {
            $usernameExists = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(User::class, 'u')
                ->where('u.username = :username')
                ->setParameter('username', $frs['ice_o'])
                ->getQuery()
                ->getOneOrNullResult();

            if ($usernameExists) {
                $fournisseurs[$key]['existsInUserTable'] = true;
            } else {
                $fournisseurs[$key]['existsInUserTable'] = false;
            }
        }
        return new JsonResponse([
            'fournisseurs' => $fournisseurs
        ]);
    }

    #[Route('/ajouter', name: 'app_admin_users_ajouter')]
    public function ajouter(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response
    {
        $selectedValues = $request->get('codes');

        if (!$selectedValues) {
            return new JsonResponse('Vous devez selectionner un ou plusieurs fournisseurs!', 500);
        }
        $codes = explode(',', $selectedValues);

        $defaultPassword = '0123456789';

        foreach ($codes as $code) {
            $entityManager = $doctrine->getManager('default')->getConnection();

            $query = "SELECT nom, prenom FROM `u_p_partenaire` where ice_o ='" . $code . "';";
            $statement = $entityManager->prepare($query);
            $result = $statement->executeQuery();
            $fournisseur = $result->fetchAll();
            // dd($fournisseur);

            if ($fournisseur) {
                $user = new User();
                $user->setUsername($code);
                $user->setNom($fournisseur[0]['nom']);
                $user->setPrenom($fournisseur[0]['prenom']);
                $user->setPassword($passwordHasher->hashPassword(
                    $user,
                    $defaultPassword
                ));
                $user->setRoles(["ROLE_FRS"]);

                $this->em->persist($user);
            } else {
                return new JsonResponse('Ce fournisseur n\'a pas de ice', 500);
            }
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Les utilisateurs son bien ajoutés!',
        ]);
    }

    #[Route('/devalider/{id}', name: 'app_admin_users_devalider')]
    public function devalider(Request $request, $id,ManagerRegistry $doctrine): Response
    {
        // dd($id);
        $user = $this->em->getRepository(User::class)->find($id);
        $partenaire = $this->em->getRepository(PartenaireValide::class)->findOneBy(["partenaireId" => $user->getPartenaireId()]);
        // dd($user);
        if ($user) {
            $currentValiderValue = $user->getValide();
            $newValiderValue = ($currentValiderValue == 1) ? 2 : 1;
            $message = ($currentValiderValue == 1) ? "L'utilisateurs est bien validé!" : "L'utilisateurs est bien devalidé!";
            // dd($partenaire->getMail2());
            if($currentValiderValue == 1){
                $fieldsToUpdate = [];
                $params = [];

                // Collect non-empty fields
                if ($nom = $partenaire->getNom()) {
                    $fieldsToUpdate[] = "nom = :nom";
                    $params['nom'] = $nom;
                }
                if ($prenom = $partenaire->getPrenom()) {
                    $fieldsToUpdate[] = "prenom = :prenom";
                    $params['prenom'] = $prenom;
                }
                if ($societe = $partenaire->getSociete()) {
                    $fieldsToUpdate[] = "societe = :societe";
                    $params['societe'] = $societe;
                }
                if ($adresse = $partenaire->getAdresse()) {
                    $fieldsToUpdate[] = "adresse = :adresse";
                    $params['adresse'] = $adresse;
                }
                if ($pays = $partenaire->getMail2()) {
                    $fieldsToUpdate[] = "mail2 = :mail2";
                    $params['mail2'] = $pays;
                }
                if ($ville = $partenaire->getVille()) {
                    $fieldsToUpdate[] = "ville = :ville";
                    $params['ville'] = $ville;
                }
                if ($tel1 = $partenaire->getTel1()) {
                    $fieldsToUpdate[] = "tel1 = :tel1";
                    $params['tel1'] = $tel1;
                }
                if ($tel2 = $partenaire->getTel2()) {
                    $fieldsToUpdate[] = "tel2 = :tel2";
                    $params['tel2'] = $tel2;
                }
                if ($mail1 = $partenaire->getMail1()) {
                    $fieldsToUpdate[] = "mail1 = :mail1";
                    $params['mail1'] = $mail1;
                }
                if ($contact1 = $partenaire->getContact1()) {
                    $fieldsToUpdate[] = "contact1 = :contact1";
                    $params['contact1'] = $contact1;
                }
                if ($contact2 = $partenaire->getContact2()) {
                    $fieldsToUpdate[] = "contact2 = :contact2";
                    $params['contact2'] = $contact2;
                }
                // dd($fieldsToUpdate);
                if (!empty($fieldsToUpdate)) {
                    $setClause = implode(', ', $fieldsToUpdate);
                    $query = "UPDATE `u_p_partenaire` SET $setClause WHERE id = :id";
                    $params['id'] = $partenaire->getPartenaireId();

                    $entityManager = $doctrine->getManager('default')->getConnection();
                    // dd($query);
                    $stmt = $entityManager->prepare($query);
                    $stmt->execute($params);
                }
                $statement = $entityManager->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue(':' . $key, $value);
                }
                $stmt->execute();
            }

            $user->setValide($newValiderValue);

            $this->em->flush();
        }

        $this->em->flush();

        return $this->json([
            'message' => $message,
        ]);
    }

    #[Route('/delete/{user}', name: 'app_admin_users_delete')]
    public function delete(Request $request, User $user): Response
    {
        $user->setActive(0);
        $this->em->flush();

        return new JsonResponse('Utilisateur bien supprimer!', 200);
    }

    #[Route('/reset/{user}', name: 'app_admin_users_reset')]
    public function reset(Request $request, User $user, UserPasswordHasherInterface $passwordHasher): Response
    {
        $defaultPassword = '0123456789';

        $user->setPassword($passwordHasher->hashPassword(
            $user,
            $defaultPassword
        ));
        $this->em->flush();

        return new JsonResponse('Le mot de pass a bien réinitialiser!', 200);
    }

    #[Route('/extractionAccess/{dateDebut}/{dateFin}', name: 'extraction_access')]
    public function extraction_access(ManagerRegistry $doctrine,Request $request,$dateDebut, $dateFin )
    {
        $access =  $this->em->getRepository(AccessHistorique::class)->findAccessByDate($dateDebut, $dateFin);
        $spreadsheet = new Spreadsheet();
        // dd($access);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'USERNAME');
        $sheet->setCellValue('B1', 'NOM');
        $sheet->setCellValue('C1', 'PRENOM');
        $sheet->setCellValue('D1', 'PARTENAIRE');
        $sheet->setCellValue('E1', 'VALIDE');
        $sheet->setCellValue('F1', 'DATE-ACCESS');
        $rowCount = 2;
        foreach ($access as $ac) {
            // dd($ac->getUser());
            if($ac->getUser()->getValide() == 0 ) $valide = "Non valide";
            if($ac->getUser()->getValide() == 1 ) $valide = "En cours de validation";
            if($ac->getUser()->getValide() == 2 ) $valide = "Valide";
            $sheet->setCellValue('A' . $rowCount, $ac->getUser()->getUsername());
            $sheet->setCellValue('B' . $rowCount, $ac->getUser()->getNom());
            $sheet->setCellValue('C' . $rowCount, $ac->getUser()->getPrenom());
            $sheet->setCellValue('D' . $rowCount, $ac->getUser()->getPartenaireId() ? "Oui" : "Non");
            $sheet->setCellValue('E' . $rowCount, $valide);
            $sheet->setCellValue('F' . $rowCount, $ac->getDateAccess());

            $rowCount++;
        }


        $writer = new Xlsx($spreadsheet);
        $fileName = 'exctraction_access.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
