<?php

// includes wordpress database settings, could replaced with any other settings file
require_once("wp-config.php");
// includes own iot airclean api settings file with currently user and tokens
require_once("api.settings.php");

// TODO REPLACE with Mobile App and FCM (Firebase)

// current example Request (POST and GET both possible)
// http://www.iot-airclean.at/api.pushover.php?user=mike&minutes=10&ppm=434&token=IOTAIRCLEAN_TOKEN_NEEDED

// TODO IMPROVE SECURITY CHECK
// check token of current user station, not high secure, but for our testing purpose a quick check
if($user_token[$_REQUEST["user"]] !== urldecode($_REQUEST["token"])){
	echo "\n".$_REQUEST["user"] . ", token '" . $_REQUEST["token"] . "'";
	die("ACCESS DENIED to"); // for debug purpose
}

// TODO additional checks, but currently not necessary for first draft
// current room, minutes and ppm for alarm message
$ppm 		= $_REQUEST["ppm"];
$temperature= $_REQUEST["temperature"];
$humidity	= $_REQUEST["humidity"];
$room 		= $_REQUEST["room"];
$station	= $_REQUEST["station"];
$location	= $_REQUEST["location"];
$datetime 	= date('Y-m-d H:i:s');

// use Wordpress Settings, possible to replace with own Database settings or other existing settings file
$servername = DB_HOST;
$username 	= DB_USER;
$password 	= DB_PASSWORD;
$dbname 	= DB_NAME;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// prepare and bind
$stmt = $conn->prepare("INSERT INTO {$table_prefix}iotairclean_measurements (station, location, room, measurement_type, measurement_value, measurement_datetime) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssds", $station, $location, $room, $measurement_type, $measurement_value, $datetime);

// set parameters and execute for CO2 value
$measurement_type 	= "ppm";
$measurement_value 	= $ppm;
$stmt->execute();

// set parameters and execute for temperature
$measurement_type 	= "temperature";
$measurement_value 	= $temperature;
$stmt->execute();

// set parameters and execute for humidity
$measurement_type 	= "humidity";
$measurement_value 	= $humidity;
$stmt->execute();

$stmt->close();
$conn->close();		

echo "SUCCESS";