<?php

require('../util/Connection.php');
require('../structures/FPS.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');

if (!SessionCheck()) {
    return;
}

require('Header.php');

function formatName($name) {
    $name = preg_replace('/[^a-zA-Z ]/', '', $name);
    return trim(ucwords(strtolower($name)));
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

// Create a new Login object and set credentials
$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

if ($_SESSION['district_user'] != $person->getUsername()) {
    echo "User is logged in with different username and password";
    return;
}

// Fetch user data securely
$query = "SELECT * FROM login WHERE username='" . mysqli_real_escape_string($con, $person->getUsername()) . "'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);

if ($numrows == 0) {
    echo "Error: Username does not exist.";
    return;
}

$row = mysqli_fetch_assoc($result);

// Use password_verify to check the hashed password
if (!password_verify($person->getPassword(), $row['password'])) {
    echo "Error: Password is incorrect.";
    return;
}

// Validate latitude and longitude
if (!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')) {
    echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
    exit();
}

// Validate demand value
if (!isStringNumber($_POST["demand"]) || floatval($_POST["demand"]) < 0) {
    echo "Error : Demand capacity must be greater than or equal to 0. Given: " . $_POST["demand"];
    exit();
}


    // Latitude check (must be greater than 0 and less than 40)
if (!is_numeric($_POST["latitude"]) || floatval($_POST["latitude"]) <= 0 || $_POST["latitude"] >= 40) {
    echo "Error : Latitude must be greater than 0 and less than 40. Given: " . $_POST["latitude"];
    exit();
}

// FPS ID check (only letters and numbers allowed)
if (
    !isset($_POST["id"]) ||
    !preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])
) {
    echo "Error : FPS ID should contain only letters and numbers. Given: " . ($_POST["id"] ?? "Missing");
    exit();
}

// Longitude check (must be greater than 65 and less than 100)
if (!is_numeric($_POST["longitude"]) || floatval($_POST["longitude"]) <= 65 || floatval($_POST["longitude"]) >= 100) {
    echo "Error : Longitude must be greater than 65 and less than 100. Given: " . $_POST["longitude"];
    exit();
}
    

$district = formatName($_POST["district"]);
$latitude = $_POST["latitude"];
$longitude = $_POST["longitude"];
$name = formatName($_POST["name"]);
$id = $_POST["id"];
$type = $_POST["type"];
$demand = $_POST["demand"];
$uniqueid = uniqid("FPS_",);

// Create a new FPS object and set its properties
$FPS = new FPS;
$FPS->setUniqueid(substr($uniqueid, 0, 15));
$FPS->setDistrict(strtoupper(trim($district)));
$FPS->setLatitude($latitude);
$FPS->setLongitude($longitude);
$FPS->setName($name);
$FPS->setId($id);
$FPS->setType($type);
$FPS->setDemand($demand);
$FPS->setActive("1");

// Prepare the insert query and check for duplicates
$query_insert_check = $FPS->checkInsert($FPS);
$query_insert_result = mysqli_query($con, $query_insert_check);
$numrows_insert = mysqli_num_rows($query_insert_result);
if ($numrows_insert == 0) {
    $query = $FPS->insert($FPS);
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("District User ->" ." FPS added ->". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost));
    mysqli_query($con, $query);
    mysqli_close($con);
    echo "<script>window.location.href = '../FPS.php';</script>";
} else {
    echo "Error: Insertion failed as FPS ID already exists.";
}

// Close the database connection
mysqli_close($con);
?>
<?php require('Fullui.php'); ?>
