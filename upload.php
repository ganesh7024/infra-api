<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('analysis_tools/analysisClass.php');

$layer_name = $_POST['layer_name'];
$projection = $_POST['projection'];

if (file_exists($_FILES['shp_import']['tmp_name']) || is_uploaded_file($_FILES['shp_import']['tmp_name']))
{
	$data = new analysisManager();
	$data->layer_name =  $layer_name;
	$data->projection =  $projection;
	$data->u_file = $_FILES['shp_import'];
	$messsege = $data->uploadData();
	
	 http_response_code(201);
     echo json_encode($messsege);
	

}

?>