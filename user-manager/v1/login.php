<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once ('userMangerClass.php');

$data = file_get_contents("php://input");

$data = json_decode($data);

$test = new UserManager();

$test->email = $data->email;
$test->password = $data->password;

if ($test->loginCheck() ['status'])
{

    http_response_code(201);

    echo json_encode(array(
        "message" => "Authorised User",
        "token" => $test->loginCheck() ['token']
    ));

}

else
{

    http_response_code(503);

    echo json_encode(array(
        "message" => "Un-auth"
    ));
}

?>
