<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
$storage_original = isset($_POST['storage']) ? $_POST['storage'] : null;
require('../util/Security.php');
if ($storage_original === '0' || $storage_original === 0) {
    $_POST['storage'] = '0';
}
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
    echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
    exit();
}

// Validate storage value
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
$Warehouse->setDistrict(strtoupper(trim($district)));
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
