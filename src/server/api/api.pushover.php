<?php

// TODO REPLACE with Mobile App and FCM (Firebase)

// current example Request (POST and GET both possible)
// http://www.iot-airclean.at/api.pushover.php?user=mike&minutes=10&ppm=434&token=IOTAIRCLEAN_TOKEN_NEEDED

// app token for iot airclean, this is the app token for https://pushover.net
$app_token = "APP_TOKEN";

// user tokens for each user stored in pushover, there is only a user token needed
$user_key = array (
	"mike" 		=> "PUSHOVER_TOKEN_NEEDED",
	"..." 		=> "...",

);
// user token mapping for iotairclean api, that only a mapping token and a user is needed to configured at an iotairclean station
$user_token = array (
	"mike" 		=> "IOTAIRCLEAN_TOKEN_NEEDED",
	"..." 		=> "...",
);

// TODO IMPROVE SECURITY CHECK
// check token of current user station, not high secure, but for our testing purpose a quick check
if($user_token[$_REQUEST["user"]] !== urldecode($_REQUEST["token"])){
	die("ACCESS DENIED to");// '" . $_REQUEST["user"] . "' and token '" . $_REQUEST["token"] . "'"); // for debug purpose
}

// TODO additional checks, but currently not necessary for first draft
// current room, minutes and ppm for alarm message
$ppm 		= $_REQUEST["ppm"];
$minutes 	= $_REQUEST["minutes"];
$room 		= $_REQUEST["room"];
$msg 		= $_REQUEST["msg"];

// if there is no room defined, we send a message generated from python background service
if($room != ""){
	// simple demo message for first tests
	$msg = "$ppm ppm CO2 erreicht, bitte in $minutes Minuten spätestens lüften";
}	
		

// get current user token from pushover
$current_user_key = $user_key[$_REQUEST["user"]];

// finally prepare everything for user notification by pushover
curl_setopt_array($ch = curl_init(), array(
  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
  CURLOPT_POSTFIELDS => array(
    "token" => $app_token,
    "user" => $current_user_key,
    "message" => $msg,
  ),
  CURLOPT_SAFE_UPLOAD => true,
  CURLOPT_RETURNTRANSFER => true,
));
curl_exec($ch);
curl_close($ch);

echo "SUCCESS";