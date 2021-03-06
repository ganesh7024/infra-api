<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('././config/dbHandler.php');

$json = $_POST['json'];
$json_array = json_decode($json, TRUE);

//print_r($json_array);
$sql = array();
forEach($json_array as $value) {
	$json = json_encode($value['edit']['geojson']);
    $gid = json_encode($value['edit']['gid']);
	$layer_name = json_encode($value['edit']['layerName']);
	
	array_push($sql,"update $layer_name set geom =  ST_SetSRID(ST_GeomFromGeoJSON('$json'),3857) where gid ='$gid'");

}


 
 $update = pg_query(DBCONNECT, implode(";",$sql));

 if($update){
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
