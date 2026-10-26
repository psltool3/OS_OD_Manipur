<?php

require('../util/Connection.php');
require('../structures/DCP.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
$demand_original = isset($_POST['demand']) ? $_POST['demand'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;
require('../util/Security.php');
if ($demand_original === '0' || $demand_original === 0 || $demand_original === '0.00' || (is_numeric($demand_original) && floatval($demand_original) == 0)) {
    $_POST['demand'] = '0';
}
if ($name_original !== null) {
    $_POST['name'] = $name_original;
}
require ('../util/Encryption.php');
require('../util/Logger.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

function formatName($name) {
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    // Check if the value is a number and not a string
    if (!is_numeric($value)) {
        return false;
    }
	
    // Convert the value to a float
    $coordinate = floatval($value);

    // Check if it's latitude or longitude and validate within the range
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

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(!isValidCoordinate($_POST["latitude"],'latitude')){
	echo "Error : Latitude must be greater than 0 and less than 40. Given: " . $_POST["latitude"];
	exit();
}

if(!isValidCoordinate($_POST["longitude"],'longitude')){
	echo "Error : Longitude must be greater than 65 and less than 100. Given: " . $_POST["longitude"];
	exit();
}

// FCI ID check (only letters and numbers allowed)
if (
    !isset($_POST["id"]) ||
    !preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])
) {
    echo "Error : FCI ID should contain only letters and numbers. Given: " . ($_POST["id"] ?? "Missing");
    exit();
}

if(!isStringNumber($_POST["demand"])){
	echo "Error : Check Total FRice Offereds Value";
	exit();
}
    



$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
$district = $_POST["district"];
$latitude = $_POST["latitude"];
$longitude = $_POST["longitude"];
$name = formatName($_POST["name"]);
$id = $_POST["id"];
$type = $_POST["type"];
$demand = $_POST["demand"];
$uniqueid = uniqid("DCP_",);


$DCP = new DCP;
$DCP->setUniqueid(substr($uniqueid,0,15));
$DCP->setDistrict(strtoupper(trim($district)));
$DCP->setLatitude($latitude);
$DCP->setLongitude($longitude);
$DCP->setName($name);
$DCP->setId($id);
$DCP->setType($type);
$DCP->setDemand($demand);
$DCP->setActive("1");

$query_insert_check = $DCP->checkInsert($DCP);
$query_insert_result = mysqli_query($con, $query_insert_check);
$numrows_insert = mysqli_num_rows($query_insert_result);
if($numrows_insert==0){
	$query = $DCP->insert($DCP);
	mysqli_query($con, $query);
	mysqli_close($con);
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("User ->" ." DCP added ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));
	echo "<script>window.location.href = '../DCP.php';</script>";
}
else{
	echo "Error : Error in Insertion as DCP id already exist";
}
} 
else{
    echo "Error : Password or Username is incorrect";
}


?>
<?php require('Fullui.php');  ?>
