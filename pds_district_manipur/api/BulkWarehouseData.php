<?php
require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
ini_set('max_execution_time', 3000);
session_start();

require('Header.php');

$mapData = [
    "District" => "district",
    "Name of Warehouse" => "name",
    "Warehouse ID" => "id",
    "Motorable/Non-Motorable" => "type",
    "Warehouse Type" => "warehousetype",
    "Latitude" => "latitude",
    "Longitude" => "longitude",
    "Storage" => "storage",
	"Active/Not-Active" => "active"
];

// Reverse mapping
$reverseMapData = array_flip($mapData);

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]); // Store plain password temporarily

// Fetch the hashed password from the database
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);

if($numrows == 0){
	echo "Error : Username not found";
	return;
}

$row = mysqli_fetch_assoc($result);
$hashed_password = $row['password']; // Assuming the 'password' column stores the hashed password

// Verify the password
if (!password_verify($person->getPassword(), $hashed_password)) {
	echo "Error : Password is incorrect";
	return;
}

if($_SESSION['district_user'] != $person->getUsername()){
	echo "User is logged in with a different username";
	return;
}

// Process the rest of the code for warehouse handling...

$districts = [];
$query = "SELECT name FROM districts WHERE 1";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);
if($numrows > 0){
	while($row = mysqli_fetch_assoc($result)){
		if(strtolower($row["name"]) == strtolower($_SESSION["district_district"])){
			array_push($districts, strtoupper(trim($row["name"])));
		}
	}
}

function formatName($name) {
	$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
    $name = ucwords(strtolower($name));
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    if (!is_numeric($value)) {
        return false;
    }

    $coordinate = floatval($value);
    switch ($coordinateType) {
        case 'latitude':
            return ($coordinate > 0 && $coordinate < 40);
        case 'longitude':
            return ($coordinate > 65 && $coordinate < 100);
        default:
            return false;
    }
}

function isStringNumber($stringValue) {
    return is_numeric($stringValue) && floatval($stringValue) >= 0;
}

$redirect = 1;

try{
	$fileName = $_FILES["file"]["tmp_name"];
	if ($_FILES["file"]["size"] > 0) {
		$file = fopen($fileName, "r");
		$i = 0;
		$district = -1;
		$name = -1;
		$id = -2;
		$warehousetype = -3;
		$type = -4;
		$latitude = -5;
		$longitude = -6;
		$storage = -7;
		$active = -1;
		while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
			if($i>0){
				if($district < 0 || $name < 0 || $id < 0 || $type < 0 || $storage < 0 || $latitude < 0 || $longitude < 0 || $warehousetype < 0 || $active < 0){
					echo "Error : You have modified Template Header, please check";
					exit();
				}
				if(!isValidCoordinate($column[$latitude], 'latitude') || !isValidCoordinate($column[$longitude], 'longitude')){
					echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
					echo "</br>";
					$redirect = 0;
				}
				if(!isStringNumber($column[$storage]) || floatval($column[$storage]) < 0){
					echo "Error : Storage capacity must be greater than or equal to 0. Given: ".$column[$storage];
					echo "</br>";
					$redirect = 0;
				}
				if(!in_array(strtoupper(trim($column[$district])), $districts)){
					echo "Error : Check District Name: ".$column[$district];
					echo "</br>";
					$redirect = 0;
				}
				if(!($column[$active] == 0 || $column[$active] == 1)){
					echo "Error : Check value of active/inactive column: ".$column[$active];
					echo "</br>";
					$redirect = 0;
				}
				
				if (
					!isset($column[$id]) ||
					!preg_match('/^[A-Za-z0-9]+$/', $column[$id])
				) {
					echo "Error: WAREHOUSE ID should not contain spaces or any special characters: " . ($column[$id] ?? 'Missing');
					echo "<br>";
					$redirect = 0;
				}
				
				if (!is_numeric($column[$latitude]) || floatval($column[$latitude]) <= 0 || floatval($column[$latitude]) >= 40) {
					echo "Error : Latitude must be greater than 0 and less than 40. Given: " . $column[$latitude];
					echo "</br>";
					$redirect = 0;
				}

				// Longitude check (must be greater than 65 and less than 100)
				if (!is_numeric($column[$longitude]) || floatval($column[$longitude]) <= 65 || floatval($column[$longitude]) >= 100) {
					echo "Error : Longitude must be greater than 65 and less than 100. Given: " . $column[$longitude];
					echo "</br>";
					$redirect = 0;
				}
			}
			else{
				for($j = 0; $j < count($column); $j++){
					switch($column[$j]){
						case $reverseMapData["district"]:
							$district = $j;
							break;
						case $reverseMapData["latitude"]:
							$latitude = $j;
							break;
						case $reverseMapData["longitude"]:
							$longitude = $j;
							break;
						case $reverseMapData["name"]:
							$name = $j;
							break;
						case $reverseMapData["id"]:
							$id = $j;
							break;
						case $reverseMapData["type"]:
							$type = $j;
							break;
						case $reverseMapData["storage"]:
							$storage = $j;
							break;
						case $reverseMapData["warehousetype"]:
							$warehousetype = $j;
							break;
						case $reverseMapData["active"]:
							$active = $j;
							break;
					}
				}
			}
			$i = $i + 1;
		}
	}
}
catch(Exception $e){
	echo "Error : Error Please check data in .csv file";
	exit();
}

if($redirect == 0){
	exit();
}

try{
	$fileName = $_FILES["file"]["tmp_name"];
	if ($_FILES["file"]["size"] > 0) {
		$file = fopen($fileName, "r");
		$i = 0;
		$district = -1;
		$name = -1;
		$id = -2;
		$warehousetype = -3;
		$type = -4;
		$latitude = -5;
		$longitude = -6;
		$storage = -7;
		$active = -8;
		while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
			if($i > 0){
				if($district < 0 || $name < 0 || $id < 0 || $type < 0 || $storage < 0 || $latitude < 0 || $longitude < 0 || $warehousetype < 0 || $active < 0){
					echo "Error : You have modified Template Header, please check";
					exit();
				}
				$Warehouse = new Warehouse;
				$uniqueid = uniqid("WH_",);
				$Warehouse->setUniqueid(substr($uniqueid, 0, 15));
				$Warehouse->setDistrict(strtoupper(trim($column[$district])));
				$Warehouse->setLatitude($column[$latitude]);
				$Warehouse->setLongitude($column[$longitude]);
				$Warehouse->setName($column[$name]);
				$Warehouse->setId($column[$id]);
				$Warehouse->setType($column[$type]);
				$Warehouse->setStorage($column[$storage]);
				$Warehouse->setWarehousetype($column[$warehousetype]);
				$Warehouse->setActive($column[$active]);
				while(true){
					$query_check = $Warehouse->check($Warehouse);
					$query_result = mysqli_query($con, $query_check);
					$numrows = mysqli_num_rows($query_result);
					if($numrows == 0){
						break;
					}
					else{
						$uniqueid = uniqid("WH_",);
						$Warehouse->setUniqueid(substr($uniqueid, 0, 15));
					}
				}
				$query_insert_check = $Warehouse->checkInsert($Warehouse);
				$query_insert_result = mysqli_query($con, $query_insert_check);
				$numrows_insert = mysqli_num_rows($query_insert_result);
				if($numrows_insert == 0){
					writeLog("District User ->" ." Warehouse Added -> ". $_SESSION['district_user'] . "| " . $Warehouse->getName());
					$query_add = $Warehouse->insert($Warehouse);
					mysqli_query($con, $query_add);
				}
				else{
					echo "Error : Warehouse with id ".$Warehouse->getId()." Already Exist</br>";
					$redirect = 2;
				}
			}
			else{
				for($j = 0; $j < count($column); $j++){
					switch($column[$j]){
						case $reverseMapData["district"]:
							$district = $j;
							break;
						case $reverseMapData["latitude"]:
							$latitude = $j;
							break;
						case $reverseMapData["longitude"]:
							$longitude = $j;
							break;
						case $reverseMapData["name"]:
							$name = $j;
							break;
						case $reverseMapData["id"]:
							$id = $j;
							break;
						case $reverseMapData["type"]:
							$type = $j;
							break;
						case $reverseMapData["storage"]:
							$storage = $j;
							break;
						case $reverseMapData["warehousetype"]:
							$warehousetype = $j;
							break;
						case $reverseMapData["active"]:
							$active = $j;
							break;
					}
				}
			}
			$i = $i + 1;
		}
		if($redirect==1){
				echo "<script>window.location.href = '../Warehouse.php';</script>";
			}
	}
}
catch(Exception $e){
	echo "Error : Error Please check data in .csv file";
	exit();
}

if($redirect == 2){
	exit();
}
