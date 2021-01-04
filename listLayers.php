<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('analysis_tools/analysisClass.php');




	$data = new analysisManager();

	$messsege = $data->getAllLayers();
	
	 http_response_code(201);
     echo json_encode($messsege);
	



?>