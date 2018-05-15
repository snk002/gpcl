<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

session_home.php
Sample access to session, created with session_login.php or
session_home.php
*/

set_include_path("../classes");
include("session.php");

$session = new TSession();
$username = $session->username;
//Possible for db case:
//$username = $session->GetStoredValue("fullname");
echo "Hello, ".$username;

?>