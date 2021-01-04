<?php
// include database and object files
require_once ('../../config/dbHandler.php');
require_once ('../../jwt/jwt.php');

class UserManager
{

    public $name;
    public $email;
    public $designation;
    public $organization;
    public $password;
    public $token;
	
    function createUser()
    {

        $uid = $this->GUID();

        $createQuery = "insert into users (id, name, email, designation, organization, password) values ('$uid', '$this->name', '$this->email', '$this->designation','$this->organization', '$this->password')";

        $execQuery = pg_query(DBCONNECT, $createQuery);

        if ($execQuery)
        {

            return true;
        }

        else
        {

            return false;
        }

    }

    function loginCheck()
    {

        $createQuery = "select * from users where email = '$this->email' and password = '$this->password'";

        $execQuery = pg_query(DBCONNECT, $createQuery);

        if (pg_num_rows($execQuery) == 1)
        {

            $issuedat_claim = time(); // issued at
            $notbefore_claim = $issuedat_claim + 10; //not before in seconds
            $expire_claim = $issuedat_claim + 60; // expire time in seconds
            $userData = pg_fetch_assoc($execQuery);

            $token = array(
                "exp" => $expire_claim,
                "data" => array(
                    "name" => $userData['name'],
                    "email" => $userData['email'],
                    "designation" => $userData['designation'],
                    "organization" => $userData['organization']
                )
            );

            $serverKey = SECREAT;

            $token = JWT::encode($token, $serverKey);

            return array(
                "status" => true,
                "message" => "Successful login.",
                "token" => $token,
                "exp" => $expire_claim
            );
        }

        else
        {

            return array(
                "status" => false
            );
        }

    }


    function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid() , '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(16384, 20479) , mt_rand(32768, 49151) , mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(0, 65535));
    }

}

?>
