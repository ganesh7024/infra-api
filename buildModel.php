<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('analysis_tools/analysisClass.php');

$layer_name = $_POST['layer_name'];
$snap_tolerance = $_POST['snap_tolerance'];
$max_pipe_length = $_POST['max_pipe_length'];
$projection = $_POST['projection'];


if (file_exists($_FILES['shp_import']['tmp_name']) || is_uploaded_file($_FILES['shp_import']['tmp_name']))
{
	$data = new analysisManager();
	$data->layer_name =  $layer_name;
	$data->snap_tolerance =  $snap_tolerance;
	$data->max_pipe_length =  $max_pipe_length;
	$data->projection =  $projection;
	$data->u_file = $_FILES['shp_import'];
	
	$messsege = $data->buildModel();
	
	 http_response_code(201);
     echo json_encode($messsege);
	

}

else {
	
	$data = new analysisManager();
	$data->layer_name =  $layer_name;
	$data->snap_tolerance =  $snap_tolerance;
	$data->max_pipe_length =  $max_pipe_length;
	$data->projection =  $projection;
	$data->existing_layer = $_POST['existing_layer'];;
	
	$messsege = $data->buildModel();
	
	 http_response_code(201);
     echo json_encode($messsege);
	
}

?>

