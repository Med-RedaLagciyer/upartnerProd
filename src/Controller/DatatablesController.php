<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DatatablesController extends AbstractController {

    /**
     * Pull a particular property from each assoc. array in a numeric array, 
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @return array        Array of property values
     */
    static function Pluck($a, $prop) {
        $out = array();
        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $out[] = $a[$i][$prop];
        }
        return $out;
    }

    /*
     * search
     *      
     */

    // static function Search($request, $columns) {
    //     $where = "";
    //     $params = $request->query;
    //     if (!empty($params->all('search')['value'])) {
    //         $search = trim($params->all('search')['value']);
    //         foreach ($columns as $key => $value) {
    //             if ($key == 0) {
    //                 $where = "and (" . $value['db'] . " LIKE '%$search%' ";
    //             } else {
    //                 $where .= " OR " . $value['db'] . " LIKE '%$search%' ";
    //             }
    //         }
    //         $where .= " )";
    //     }
    //     return $where;
    // }

    static function Search($request, $columns) {
        $where = "";
        $params = $request->query;
        if (!empty($params->all('search')['value'])) {
            $search = trim($params->all('search')['value']);
            foreach ($columns as $key => $value) {
                $dbColumnName = $value['db'];
                // Check if the column has an alias
                if (strpos($dbColumnName, ' AS ') !== false) {
                    // If alias is found, extract the column name from the alias
                    $dbColumnName = explode(' AS ', $dbColumnName)[0];
                }
                if ($key == 0) {
                    $where = " AND (" . $dbColumnName . " LIKE '%$search%' ";
                } else {
                    $where .= " OR " . $dbColumnName . " LIKE '%$search%' ";
                }
            }
            $where .= ")";
        }
        return $where;
    }

    /*
     * search
     *      
     */

    // static function Order($request, $columns) {
    //     $params = $request->query;
    //     // dd($params);
    //     $sqlRequest = "";
    //     $sqlRequest = " ORDER BY " . self::Pluck($columns, 'db')[$params->all('order')[0]['column']] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
    //     return $sqlRequest;
    // }
    
    public static function Order($request, $columns, $type = "", $userId = "") {
        $params = $request->query->all();
        
        // Check if the "order" parameter exists and is an array
        if (isset($params['order']) && is_array($params['order']) && count($params['order']) > 0) {
            $orderColumnIdx = $params['order'][0]['column'];
            $orderDir = $params['order'][0]['dir'];
    
            // Get the database column name for the ordered column
            $orderColumnDb = $columns[$orderColumnIdx]['db'];
    
            // Check if the column name contains an alias
            if (strpos($orderColumnDb, ' AS ') !== false) {
                // If the column name contains an alias, extract the alias and use it in the ORDER BY clause
                $orderColumnDb = strstr($orderColumnDb, ' AS ', true);
            }
    
            // Construct the ORDER BY clause
            $sqlOrder = " ORDER BY $orderColumnDb $orderDir";
        } else {
            // If "order" parameter is missing or empty, set default ordering or do nothing
            $sqlOrder = "";
        }

        
        if($type == "rec" and $sqlOrder == " ORDER BY r.id desc"){
            // $sqlOrder = " ORDER BY CASE WHEN rep.id IS NULL THEN 0 WHEN latest_response_user_id = 10 THEN 0 ELSE 1 END ASC";
            $sqlOrder = " ORDER BY r.created DESC";
        }
        // dd($sqlOrder);
    
        // Construct the LIMIT clause
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $sqlLimit = " LIMIT $start, $length";
    
        // Combine ORDER BY and LIMIT clauses
        $sqlRequest = $sqlOrder . $sqlLimit;
    
        return $sqlRequest;
    }
    
    
    
    
    

}
