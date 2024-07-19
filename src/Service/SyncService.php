<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Mapping\MappingException;
use DateTime;
use DateTimeImmutable;

class SyncService
{
    private $httpClient;
    private $entityManager;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function synchronize($tableName, $lastId)
    {
        switch ($tableName) {
            case 'ua_t_facturefrsdet':
                $query = "SELECT det.* FROM ua_t_facturefrsdet det INNER JOIN ua_t_facturefrscab cab on cab.id = det.ua_t_facturefrscab_id WHERE det.id > " . $lastId . "  AND cab.active = 1 limit 50 ;";
                break;
            case 'ua_t_livraisonfrsdet':
                $query = "SELECT det.* FROM ua_t_livraisonfrsdet det INNER JOIN ua_t_livraisonfrscab cab on cab.id = det.ua_t_livraisonfrscab_id WHERE det.id > " . $lastId . " AND cab.active = 1 limit 50;";
                break;
            case 'ua_t_commandefrsdet':
                $query = "SELECT det.* FROM ua_t_commandefrsdet det INNER JOIN ua_t_commandefrscab cab on cab.id = det.ua_t_commandefrscab_id WHERE det.id > " . $lastId . " AND cab.active = 1 limit 50;";
                break;
            default:
                $query = "SELECT * FROM " . $tableName . " WHERE id > " . $lastId . " AND active = 1 limit 50 ;";
        }

        // dd($query);
        $response = $this->httpClient->request(
            'GET',
            'https://ugouv-fcz.ma/api/upartner/sync',
            [
                'headers' => [
                    'Authorization' => 'Bearer your_api_key_here'
                ],
                'query' => [
                    "query" => $query
                ],
                'verify_peer' => false,
            ]
        );
        $data = $response->toArray();
        // dd($data);

        $lastInsertedId = $this->insertData($tableName, $data);
        $count = count($data);

        return [
            'countDone' => $count,
            'lastInsertedId' => $lastInsertedId,
        ];
        // try {

        //     $this->insertData($data);
        // } catch (\Exception $e) {
        //     $this->logger->error('Data synchronization failed: ' . $e->getMessage());
        // }
    }

    public function insertData(string $tableName, array $data): string
    {
        $connection = $this->entityManager->getConnection();
        if (empty($data)) {
            $connection->executeStatement('SET foreign_key_checks = 1');
            $sql = "SELECT COUNT(*), MAX(id) AS last_id FROM " . $tableName . ";";
            $statement = $connection->prepare($sql);
            $result = $statement->executeQuery();
            $count = $result->fetchAll();
            $lastInsertedId = $count[0]["last_id"];
            return $lastInsertedId;
        }

        $columns = $connection->getSchemaManager()->listTableColumns($tableName);

        // $sql = "SELECT COLUMN_NAME, DATA_TYPE 
        // FROM INFORMATION_SCHEMA.COLUMNS 
        // WHERE TABLE_SCHEMA = :database 
        // AND TABLE_NAME = :table";

        // $stmt = $connection->prepare($sql);
        // $stmt->bindValue('database', $connection->getDatabase());
        // $stmt->bindValue('table', $tableName);
        // $result = $stmt->execute();

        // $columns = $result->fetchAll();

        // dd($columns);
        $validFields = array_map(fn ($column) => $column->getName(), $columns);

        $connection->executeStatement('SET foreign_key_checks = 0');

        $firstItem = reset($data);
        $fields = array_keys($firstItem);

        // dd($validFields, $fields);

        $fields = array_filter($fields, fn ($field) => in_array($field, $validFields));

        if (empty($fields)) {
            $connection->executeStatement('SET foreign_key_checks = 1');
            // return "No valid fields found for insertion.";
            dd("No valid fields found for insertion");
        }

        $placeholders = array_map(
            function ($row) use ($fields) {
                return '(' . implode(', ', array_map(fn ($field) => ':' . $field . $row, $fields)) . ')';
            },
            array_keys($data)
        );

        $query = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $placeholders);

        $stmt = $connection->prepare($query);

        foreach ($data as $rowIndex => $item) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $item)) {
                    $stmt->bindValue(':' . $field . $rowIndex, $item[$field]);
                } else {
                    continue;
                }
            }
        }

        $stmt->execute();

        $connection->executeStatement('SET foreign_key_checks = 1');
        $sql = "SELECT COUNT(*), MAX(id) AS last_id FROM " . $tableName . ";";
        $statement = $connection->prepare($sql);
        $result = $statement->executeQuery();
        $count = $result->fetchAll();
        return $count[0]["last_id"];
    }
}
