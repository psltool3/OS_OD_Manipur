<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
require('../util/Security.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

if (!SessionCheck()) {
    return;
}

require('Header.php');

function formatName($name) {
    $name = preg_replace('/[^a-zA-Z ]/', '', $name);
    return trim(ucwords(strtolower($name)));
}

function isValidCoordinate($value, $coordinateType) {
    // Check if the value is a number
    if (!is_numeric($value)) {
        return false;
    }

    // Convert to float
    $coordinate = floatval($value);

    // Validate latitude and longitude
    return ($coordinateType === 'latitude' && $coordinate >= -90 && $coordinate <= 90) ||
           ($coordinateType === 'longitude' && $coordinate >= -180 && $coordinate <= 180);
}

function isStringNumber($stringValue) {
    return is_numeric($stringValue);
}

// Create a new Login object and set credentials
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Check if the logged-in user matches the session user
if ($_SESSION['district_user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password";
    return;
}

// Fetch user data without exposing the password
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

// Verify password against the stored hashed password
if (!password_verify($person->getPassword(), $row['password'])) {
    echo "Error: Password is incorrect.";
    return;
}

// Validate latitude and longitude
if (!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')) {
    echo "Error: Check Latitude and Longitude Value.";
    exit();
}

// Validate storage value
if (!isStringNumber($_POST["storage"])) {
    echo "Error: Check Storage Value.";
    exit();
}


    // Latitude check (must be less than 40)
if (!is_numeric($_POST["latitude"]) || $_POST["latitude"] >= 40) {
    echo "Error : Latitude must be less than 40. Given: " . $_POST["latitude"];
    exit();
}

// FPS ID check (only letters and numbers allowed)
if (
    !isset($_POST["id"]) ||
    !preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])
) {
    echo "Error : WAREHOUSE ID should contain only letters and numbers. Given: " . ($_POST["id"] ?? "Missing");
    exit();
}

// Longitude check (must be more than 65)
if (!is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65) {
    echo "Error : Longitude must be more than 65. Given: " . $_POST["longitude"];
    exit();
}
    

// Prepare and sanitize input data
$district = formatName($_POST["district"]);
$latitude = $_POST["latitude"];
$longitude = $_POST["longitude"];
$name = formatName($_POST["name"]);
$id = $_POST["id"];
$type = $_POST["type"];
$storage = $_POST["storage"];
$warehousetype = $_POST["warehousetype"];
$uniqueid = $_POST["uniqueid"];
$active = $_POST["active"];

$Warehouse = new Warehouse;
$Warehouse->setUniqueid($uniqueid);
$Warehouse->setDistrict($district);
$Warehouse->setLatitude($latitude);
$Warehouse->setLongitude($longitude);
$Warehouse->setName($name);
$Warehouse->setId($id);
$Warehouse->setType($type);
$Warehouse->setStorage($storage);
$Warehouse->setWarehousetype($warehousetype);
$Warehouse->setActive($active);

// Prepare the update query
$query = $Warehouse->update($Warehouse);

// Execute the update
if (mysqli_query($con, $query)) {
    // Redirect on success
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("District User ->" ." Warehouse Edit->". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost));

    echo "<script>window.location.href = '../Warehouse.php';</script>";
} else {
    echo "Error: Unable to update warehouse data.";
}

// Close the database connection
mysqli_close($con);
?>
<?php require('Fullui.php'); ?>
