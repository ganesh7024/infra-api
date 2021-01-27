<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('././config/dbHandler.php');

$layer_name = $_POST['layer_name'];
$gid = $_POST['gid'];
$json = $_POST['json'];



 $update = pg_query(DBCON, "update $layer_name set geom =  ST_SetSRID(ST_GeomFromGeoJSON('$json'),3857) where gid ='$gid'");

if($update) {
	        http_response_code(201);
	        echo json_encode( array(
			    "status" => true,
                "upload_message" => "The feature has been Edited sucessfully!!!"

            ));
}

else  {
	        http_response_code(401);
	        echo json_encode(  array(
			    "status" => false,
                "upload_message" => "Failed to Edited the feature!!!"

            ));	
	
} 



?>
