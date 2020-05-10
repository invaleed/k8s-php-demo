<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

define('DB_SERVER', '__DB_SERVER__');
define('DB_USERNAME', '__DB_USER__');
define('DB_PASSWORD', '__DB_PASSWORD__');
define('DB_NAME', '__DB_NAME__');
  
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
  
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
