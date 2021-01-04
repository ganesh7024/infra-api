<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once ('config/dbHandler.php');

if (file_exists($_FILES['shp_import']['tmp_name']) || is_uploaded_file($_FILES['shp_import']['tmp_name']))
{
	
	$messsege = uploadShp($_FILES['shp_import']);

}

function uploadShp($fileName)
{

    $filename = $fileName['name'];
    $filename = substr($filename, 0, -4);

    $path = ROOTDIR . "/upload_files/";

    $uni_name = $filename . '_' . uniqid();
    $uni_name = strtolower($uni_name);
    $uni_name = str_replace(' ', '_', $uni_name);

    mkdir($path . $uni_name, 0777);

    $move_location = $path . $uni_name . "/" . $uni_name . '.zip';

    move_uploaded_file($fileName['tmp_name'], $move_location);

    unzip_file($move_location, $path . $uni_name . "/");

    $folderPath = $path . $uni_name . "/";

    $shp_location = glob($folderPath . "*.shp");

    $shx_location = glob($folderPath . "*.shx");
    $dbf_location = glob($folderPath . "*.dbf");
    $prj_location = glob($folderPath . "*.prj");

    if ($shp_location && $shx_location && $dbf_location && $prj_location)
    {

        $cmd = '"C:\Program Files\PostgreSQL\10\bin\shp2pgsql" -s 32643 -c "' . $shp_location[0] . '" ' . $uni_name;

        $queries = shell_exec($cmd);

        $insert_to_postgis = pg_query(DBCONNECT, $queries);

    
            return array(
                "upload_message" => "<p class='text-success'><b>" . $filename . " is valid GIS Data and upload sucessfull</b></p>",
                "u_status" => 't',
                "u_name" => $uni_name
            );
    }


}


	function unzip_file($file, $destination){
		// create object
		$zip = new ZipArchive() ;
		// open archive
		if ($zip->open($file) !== TRUE) {
			return false;
		}
		// extract contents to destination directory
		$zip->extractTo($destination);
		// close archive
		$zip->close();
        return true;
	}

?>

