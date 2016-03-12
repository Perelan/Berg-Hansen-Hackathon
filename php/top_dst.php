<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//if session is not started
if(!session_id()){
    session_start();
}

//get code for connecting and fetching data from API
require_once('bridge.php');

$session = sendRequest('v1/lists/top/destinations?origincountry=US&topdestinations=50&lookbackweeks=2', null);

$session = $session['Destinations'];

echo json_encode($session, 128);
