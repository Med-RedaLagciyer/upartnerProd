<?php

namespace App\Controller\Admin;

use App\Service\SyncService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;

#[Route('/syncronize')]
class SyncController extends AbstractController
{
    private $syncService;
    private $entityManager;

    public function __construct(SyncService $syncService, EntityManagerInterface $entityManager)
    {
        $this->syncService = $syncService;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_admin_syncronize', options: ['expose' => true])]
    public function index(): Response
    {
        return $this->render('admin/sync/index.html.twig');
    }

    #[Route('/count', name: 'app_admin_syncronize_count', options: ['expose' => true])]
    public function count(ManagerRegistry $doctrine,): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        $tables = ["p_unite", "tr_transaction", "u_general_operation", "u_p_partenaire", "ua_t_commandefrscab", "ua_t_commandefrsdet", "ua_t_facturefrscab", "ua_t_facturefrsdet", "ua_t_livraisonfrscab", "ua_t_livraisonfrsdet", "uarticle"];

        $unionQueries = [];
        foreach ($tables as $table) {
            $unionQueries[] = "SELECT '$table' AS table_name, COUNT(*) AS count, MAX(id) AS last_id FROM $table";
        }

        $sql = implode(' UNION ALL ', $unionQueries);
        $statement = $entityManager->prepare($sql);
        $result = $statement->executeQuery();
        $counts = $result->fetchAllAssociative();

        $resultTables = [];
        foreach ($counts as $count) {
            $resultTables[$count['table_name']] = [
                'count' => $count['count'],
                'lastId' => $count['last_id'],
                'tableName' => $count['table_name'],
            ];
        }

        return new JsonResponse($resultTables);
    }

    #[Route('/count_table', name: 'app_admin_syncronize_count_table', options: ['expose' => true])]
    public function count_table(ManagerRegistry $doctrine, Request $request): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();
        $tableName = $request->get('tableName');
        $sql = "SELECT COUNT(*), MAX(id) AS last_id FROM " . $tableName . ";";
        $statement = $entityManager->prepare($sql);
        $result = $statement->executeQuery();
        $count = $result->fetchAll();
        $lastId = $count[0]["last_id"] ? $count[0]["last_id"] : 0;
        return new JsonResponse([
            'countDone' => $count[0]["COUNT(*)"],
            'lastInsertedId' => $lastId,
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/sync', name: 'app_admin_syncronize_sync', options: ['expose' => true])]
    public function sync(Request $request): Response
    {
        $tableName = $request->get('tableName');
        $lastId = $request->get('lastId');

        // dd($request);

        $syncResult = $this->syncService->synchronize($tableName, $lastId);

        return new JsonResponse([
            'countDone' => $syncResult['countDone'],
            'lastInsertedId' => $syncResult['lastInsertedId'],
        ], JsonResponse::HTTP_OK);

        // } catch (\Exception $e) {
        //     return new JsonResponse('erreur de synchronisation', 500);
        // }
    }
}
