<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('config/dbHandler.php');


$data = file_get_contents("php://input");

$data = json_decode($data);
$tableName =$data->layerName;
$tempTab = "rev_".uniqid();

$createQuery = pg_query(DBCONNECT, "create table $tempTab as select * from $tableName");

$selectQery = pg_query(DBCONNECT, "select * from $tempTab");

//interchaging of nodes where it has negative flow

while ($row = pg_fetch_array($selectQery))
        {
					
			$result_flo = $row['result_flo'];
            if ($result_flo < 0 ) {
				
				$node1 = $row['node1'];
				$node2 = $row['node2'];
				$gid = $row['gid'];
				
				$updateQuery = pg_query(DBCONNECT, "update $tempTab set node1 = '$node2', node2 = '$node1', the_geom = st_reverse(the_geom) where gid = $gid");
				
				
			}
             			
		}
		
$getColQuery = pg_query("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pipes' and data_type != 'USER-DEFINED'");

$columnNames = null;

while ($row = pg_fetch_array($getColQuery))
        {
			$columnNames .= $row['column_name'].",";
			
		}
		
		
$columnNames =  substr($columnNames, 0, -1);


$geoJson = pg_query(DBCONNECT, "SELECT row_to_json(fc) FROM ( SELECT 'FeatureCollection' AS type , array_to_json(array_agg(f)) AS features FROM ( SELECT 'Feature' AS type , ST_AsGeoJSON(the_geom)::json as geometry , (  SELECT row_to_json(t) FROM ( SELECT $columnNames	) AS t) AS properties FROM $tempTab ) AS f) AS fc");

$geoJson = pg_fetch_assoc($geoJson);

echo $geoJson['row_to_json'];

?>