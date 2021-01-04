<?php
require_once('config.php');

define ('DBCONNECT', pg_connect("host=".HOST." dbname=".DB_NAME." user=".USER." password=".PASS));


//define ('DBCLOSE', pg_close(DBCONNECT));

?>