<?php

$apiKey = base64_encode("V1:p542inzssp1am6et:DEVCENTER:EXT"); // Insert apiKey here
$apiSecret = base64_encode("RsmVI5y7"); // Insert apiSecret here
$tokenKey = base64_encode($apiKey.":".$apiSecret);

function getAuthToken(){
  global $tokenKey;

  $current_time = time();
  // Validate if expire timestamp exists or if the token has expired (then renew the token);
  if($_SESSION['expire_ts'] < $current_time) {

  	$ch = curl_init("https://api.test.sabre.com/v2/auth/token");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,array('Authorization: Basic ' . $tokenKey, 'Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($ch);
		curl_close($ch);

  	$session = json_decode($json, true);

  	$_SESSION['access_token'] = $session['access_token'];
  	$_SESSION['token_type'] = $session['token_type'];
  	$_SESSION['expires_in'] = $session['expires_in'];
  	$_SESSION['expire_ts'] = 1200 + time();

  }
}

function sendRequest($payload, $data){
  $current_time = time();
  global $tokenKey;
  $retVal = 'null';
  if(!isset($_SESSION['expire_ts']) || $_SESSION['expire_ts'] < $current_time){
    //try to get authentication token
    getAuthToken();
  }

  $ch = curl_init("https://api.test.sabre.com/" . $payload);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if($data == null){
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded;charset=UTF-8','Authorization:Bearer ' . $_SESSION['access_token']));
	}else{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json;charset=UTF-8','Transfer-Encoding: chunked','Authorization:Bearer ' . $_SESSION['access_token']));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}

	$json = curl_exec($ch);
	curl_close($ch);

	$session = json_decode($json, true);

  return $session;
}
