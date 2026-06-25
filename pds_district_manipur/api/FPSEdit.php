<?php

require('../util/Connection.php');
require('../structures/FPS.php');
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
    if (!is_numeric($value)) {
        return false;
    }
	
    $coordinate = floatval($value);

    switch ($coordinateType) {
        case 'latitude':
            return ($coordinate >= -90 && $coordinate <= 90);
        case 'longitude':
            return ($coordinate >= -180 && $coordinate <= 180);
        default:
            return false;
    }
}

function isStringNumber($stringValue) {
    return is_numeric($stringValue);
}

// Create a new Login object and set credentials
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Check if the session username matches
if ($_SESSION['district_user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password.";
    return;
}


// Validate latitude and longitude
if (!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')) {
    echo "Error: Check Latitude and Longitude Value.";
    exit();
}

// Validate demand value
if (!isStringNumber($_POST["demand"])) {
    echo "Error: Check Demand Value of FRice.";
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
    echo "Error : FPS ID should contain only letters and numbers. Given: " . ($_POST["id"] ?? "Missing");
    exit();
}

// Longitude check (must be more than 65)
if (!is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65) {
    echo "Error : Longitude must be more than 65. Given: " . $_POST["longitude"];
    exit();
}
    

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){

    $district = formatName($_POST["district"]);
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = formatName($_POST["name"]);
    $id = $_POST["id"];
    $type = $_POST["type"];
    $demand = $_POST["demand"];
    $uniqueid = $_POST["uniqueid"];
    $active = $_POST["active"];
    
    // Create a new FPS object and set its properties
    $FPS = new FPS;
    $FPS->setUniqueid($uniqueid);
    $FPS->setDistrict($district);
    $FPS->setLatitude($latitude);
    $FPS->setLongitude($longitude);
    $FPS->setName($name);
    $FPS->setId($id);
    $FPS->setType($type);
    $FPS->setDemand($demand);
    $FPS->setActive($active);
    
    $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("District User ->" ." FPS Edit->". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost));
    
    // Prepare the update query
    $query = $FPS->update($FPS);
    mysqli_query($con, $query);
    
    // Close the database connection
    mysqli_close($con);
    
    // Redirect to the FPS page
    echo "<script>window.location.href = '../FPS.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php'); ?>
