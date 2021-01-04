<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once ('userMangerClass.php');

$data = file_get_contents("php://input");

$data = json_decode($data);

$test = new UserManager();

$test->name = $data->name;
$test->email = $data->email;
$test->designation = $data->designation;
$test->organization = $data->organization;
$test->password = $data->password;

if ($test->createUser())
{

    http_response_code(201);

    echo json_encode(array(
        "message" => "User was created."
    ));

}

else
{

    http_response_code(503);

    echo json_encode(array(
        "message" => "Unable to create User."
    ));
}

?>
