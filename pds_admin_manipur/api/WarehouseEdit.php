<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
$storage_original = isset($_POST['storage']) ? $_POST['storage'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;
require('../util/Security.php');
if ($storage_original === '0' || $storage_original === 0) {
    $_POST['storage'] = '0';
}
if ($name_original !== null) {
    $_POST['name'] = $name_original;
}
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
    return;
}

require('Header.php');

function formatName($name) {
    return trim($name);
}

// Function to validate coordinates (latitude/longitude)
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

// Function to check if a string is numeric and non-negative
function isStringNumber($stringValue) {
    return is_numeric($stringValue) && floatval($stringValue) >= 0;
}

// Initialize the Login object
$person = new Login;
$person->setUsername($_POST["username"]);

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));


if ($_SESSION['user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password";
    return;
}

 if (!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')) {
    echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
    exit();
 }

if (!isStringNumber($_POST["storage"]) || floatval($_POST["storage"]) < 0) {
    echo "Error : Storage capacity must be greater than or equal to 0. Given: " . $_POST["storage"];
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
    echo "Error : WAREHOUSE ID should contain only letters and numbers. Given: " . ($_POST["id"] ?? "Missing");
    exit();
}

// Longitude check (must be greater than 65 and less than 100)
if (!is_numeric($_POST["longitude"]) || floatval($_POST["longitude"]) <= 65 || floatval($_POST["longitude"]) >= 100) {
    echo "Error : Longitude must be greater than 65 and less than 100. Given: " . $_POST["longitude"];
    exit();
}
    
// Query to retrieve the user details based on username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(empty($row)){
    die("Username or Password is incorrect");
}
$dbHashedPassword = $row['password'];
if (password_verify($person->getPassword(), $dbHashedPassword)) {
    
    $district = formatName($_POST["district"]);
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = formatName($_POST["name"]);
    $id = formatName($_POST["id"]);
    $type = $_POST["type"];
    $storage = $_POST["storage"];
    $warehousetype = $_POST["warehousetype"];
    $uniqueid = $_POST["uniqueid"];
    $active = $_POST["active"];

    // Initialize the Warehouse object and set values
    $Warehouse = new Warehouse;
    $Warehouse->setUniqueid($uniqueid);
    $Warehouse->setDistrict(strtoupper(trim($district)));
    $Warehouse->setLatitude($latitude);
    $Warehouse->setLongitude($longitude);
    $Warehouse->setName($name);
    $Warehouse->setId($id);
    $Warehouse->setType($type);
    $Warehouse->setStorage($storage);
    $Warehouse->setWarehousetype($warehousetype);
    $Warehouse->setActive($active);

    // Check if the warehouse already exists
    $query_check = $Warehouse->checkInsert($Warehouse);
    $query_result = mysqli_query($con, $query_check);
    $numrows = mysqli_num_rows($query_result);

    if ($numrows != 0) {
        $row = mysqli_fetch_assoc($query_result);
        $uniqueid_check = $row["uniqueid"];
        if ($uniqueid != $uniqueid_check) {
            echo "Error: Warehouse ID already exists. ID: " . $id;
            exit();
        }
    }

    // Update the warehouse information
    $query = $Warehouse->update($Warehouse);
    mysqli_query($con, $query);
    mysqli_close($con);
    $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("User ->" ." Warehouse Edit->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));


    // Redirect after successful update
    echo "<script>window.location.href = '../Warehouse.php';</script>";
} else {
    // Incorrect password
    echo "Error: Incorrect password";
    return;
}


?>
<?php require('Fullui.php'); ?>
