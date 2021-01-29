<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('././config/dbHandler.php');

$layer_name = $_POST['layer_name'];
$json = $_POST['json'];

$get_max_gid = pg_query(DBCONNECT, "select max(gid) from  $layer_name");
$get_max_gid = pg_fetch_assoc($get_max_gid);
$get_max_gid = $get_max_gid['max'] + 1;

$add_query = pg_query(DBCONNECT, "insert into $layer_name (gid, geom) values ($get_max_gid, ST_SetSRID(ST_GeomFromGeoJSON('$json'),3857))");

if ($add_query)
{
    http_response_code(201);
    echo json_encode(array(
        "status" => true,
        "upload_message" => "The feature has been Added sucessfully!!!"

    ));
}

else
{
    http_response_code(401);
    echo json_encode(array(
        "status" => false,
        "upload_message" => "Failed to Add the feature!!!"

    ));

}

?>
