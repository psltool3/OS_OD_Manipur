<?php

require('../util/Connection.php');
require('../structures/FPS.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
require('../util/Security.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

// Check if session is valid
if (!SessionCheck()) {
    return;
}

require('Header.php');

function formatName($name) {
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
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

// Create Login object and set credentials
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Check if the current session user matches the one trying to perform the action
if ($_SESSION['user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password.";
    return;
}

// Use prepared statements to prevent SQL injection
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

// Validate latitude and longitude
if (!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')) {
    echo "Error: Check Latitude and Longitude Value";
    exit();
}

// Validate demand value
if (!isStringNumber($_POST["demand"])) {
    echo "Error: Check Demand Value";
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
    

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){

    $district = formatName($_POST["district"]);
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = formatName($_POST["name"]);
    $id = formatName($_POST["id"]);
    $type = $_POST["type"];
    $demand = $_POST["demand"];
    $uniqueid = $_POST["uniqueid"];
    $active = $_POST["active"];
    
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
    
    // Check if FPS ID already exists
    $query_check = $FPS->checkInsert($FPS);
    $query_result = mysqli_query($con, $query_check);
    $numrows = mysqli_num_rows($query_result);
    if ($numrows != 0) {
        $row = mysqli_fetch_assoc($query_result);
        $uniqueid_check = $row["uniqueid"];
        if ($uniqueid != $uniqueid_check) {
            echo "Error: In updating data as FPS id already exists ID: " . $id;
            echo "</br>";
            exit();
        }
    }
    
    // Update the FPS record
    $query = $FPS->update($FPS);
    mysqli_query($con, $query);
    
    // Close the database connection
    mysqli_close($con);
    
    $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("User ->" ." FPS Edit->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));
    
    
    // Redirect to FPS.php
    echo "<script>window.location.href = '../FPS.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}


?>

<?php require('Fullui.php'); ?>
