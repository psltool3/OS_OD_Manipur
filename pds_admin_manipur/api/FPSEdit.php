<?php

require('../util/Connection.php');
require('../structures/FPS.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
$demand_original = isset($_POST['demand']) ? $_POST['demand'] : null;
$demand_rice_original = isset($_POST['demand_rice']) ? $_POST['demand_rice'] : null;
$demand_frice_original = isset($_POST['demand_frice']) ? $_POST['demand_frice'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;
require('../util/Security.php');
if ($demand_original === '0' || $demand_original === 0) { $_POST['demand'] = '0'; }
if ($demand_rice_original === '0' || $demand_rice_original === 0) { $_POST['demand_rice'] = '0'; }
if ($demand_frice_original === '0' || $demand_frice_original === 0) { $_POST['demand_frice'] = '0'; }
if ($name_original !== null) { $_POST['name'] = $name_original; }
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

// Check if session is valid
if (!SessionCheck()) {
    return;
}

require('Header.php');

function formatName($name) {
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
    echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
    exit();
}

// Validate demand value
if (!isStringNumber($_POST["demand"]) || floatval($_POST["demand"]) < 0) {
    echo "Error : Demand capacity must be greater than or equal to 0. Given: " . $_POST["demand"];
    exit();
}


    // Latitude check (must be greater than 0 and less than 40)
if (!is_numeric($_POST["latitude"]) || floatval($_POST["latitude"]) <= 0 || floatval($_POST["latitude"]) >= 40) {
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
    $FPS->setDistrict(strtoupper(trim($district)));
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
