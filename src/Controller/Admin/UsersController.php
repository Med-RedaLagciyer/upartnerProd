<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


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
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where active = 1";
        // dd($params->all('columns')[0]);

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
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);

            foreach (array_values($row) as $key => $value) {

                // dd($fournisseur);



                if ($key == 5) {
                    $nestedData[] = $value == 2 ?  "<i class='fa fa-check-square text-success' id='$cd'></i>" : "<i class='fa fa-times-circle text-danger' id='$cd'></i>";
                    $nestedData[] = "<button class='btn_reinitialiser btn btn-secondary' id='$cd'><i class='fas fa-sync'></i></button>";
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
        // dd($fournisseurs);
        return new JsonResponse([
            'fournisseurs' => $fournisseurs
        ]);
    }

    #[Route('/ajouter', name: 'app_admin_users_ajouter')]
    public function ajouter(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response
    {
        $selectedValues = $request->get('codes');
        // dd($selectedValues);

        if (!$selectedValues) {
            return new JsonResponse('Vous devez selectionner un ou plusieurs fournisseurs!', 500);
        }
        // dd($request->get('codes'));
        $codes = explode(',', $selectedValues);

        $defaultPassword = '0123456789';






        // dd($codes);
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
    public function devalider(Request $request, $id): Response
    {
        // dd($id);
        $user = $this->em->getRepository(User::class)->find($id);
        if ($user) {
            $currentValiderValue = $user->getValide();
            $newValiderValue = ($currentValiderValue == 1) ? 2 : 1;
            $message = ($currentValiderValue == 1) ? "VALIDATION DU COMPTE TERMINÉE." : "COMPTE INVALIDE.";
            $user->setValide($newValiderValue);

            $this->em->flush();
        }

        $this->em->flush();
        // dd($message);
        return new JsonResponse($message, 200);
    }

    #[Route('/delete/{user}', name: 'app_admin_users_delete')]
    public function delete(Request $request, User $user): Response
    {
        $user->setActive(0);
        $this->em->flush();

        return new JsonResponse('SUPPRESSION TERMINÉE AVEC SUCCÈS', 200);
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

        return new JsonResponse('MOT DE PASSE RÉINITIALISER AVEC SUCCÈS', 200);
    }
}
