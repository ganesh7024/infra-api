<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('././config/dbHandler.php');

$layer_name = $_POST['layer_name'];
$gid = $_POST['gid'];

$delete_query = pg_query(DBCONNECT,"delete from $layer_name where gid = '$gid'");

if($delete_query) {
	        http_response_code(201);
	        echo json_encode( array(
			    "status" => true,
                "upload_message" => "The feature has been deleted sucessfully!!!"

            ));
}

else  {
	        http_response_code(401);
	        echo json_encode(  array(
			    "status" => false,
                "upload_message" => "Failed to delete the feature!!!"

            ));	
	
}

?>

