<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');

if(!SessionCheck()){
    return;
}

require('Header.php');

// Sanitize and format the name
function formatName($name) {
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name); // Only allow alphanumeric, spaces, and underscores
    $name = ucwords(strtolower($name)); // Convert to title case
    return trim($name);
}

// Validate latitude and longitude
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

// Check if a value is numeric and non-negative
function isStringNumber($stringValue) {
    return is_numeric($stringValue) && floatval($stringValue) >= 0;
}

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

// Ensure the session user matches the provided username
if($_SESSION['user'] != $person->getUsername()){
    echo "User is logged in with a different username and password";
    return;
}

// Query to retrieve the user details from the database based on the username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

// Verify the entered password against the hashed password in the database
if ($row) {
    if (password_verify($person->getPassword(), $row['password'])) {
        // Password is correct, proceed with warehouse insertion

        // Combined coordinate validation check
        if(!isValidCoordinate($_POST["latitude"], 'latitude') || !isValidCoordinate($_POST["longitude"], 'longitude')){
            echo "Error : Latitude must be greater than 0 less than 40 and long greater than 65 and less than 100";
            exit();
        }

        // Validate storage value (must be non-negative)
        if(!isStringNumber($_POST["storage"]) || floatval($_POST["storage"]) < 0){
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
			

        // Sanitize and format warehouse inputs
        $district = formatName($_POST["district"]);
        $latitude = $_POST["latitude"];
        $longitude = $_POST["longitude"];
        $name = formatName($_POST["name"]);
        $id = formatName($_POST["id"]);
        $type = $_POST["type"];
        $storage = $_POST["storage"];
        $warehousetype = $_POST["warehousetype"];
        $uniqueid = uniqid("WH_", true);

        // Set the warehouse details
        $Warehouse = new Warehouse;
        $Warehouse->setUniqueid(substr($uniqueid, 0, 15));  // Limit the unique ID to 15 characters
        $Warehouse->setDistrict(strtoupper(trim($district)));
        $Warehouse->setLatitude($latitude);
        $Warehouse->setLongitude($longitude);
        $Warehouse->setName($name);
        $Warehouse->setId($id);
        $Warehouse->setType($type);
        $Warehouse->setStorage($storage);
        $Warehouse->setWarehousetype($warehousetype);
        $Warehouse->setActive("1");

        // Check if the warehouse already exists
        $query_insert_check = $Warehouse->checkInsert($Warehouse);
        $query_insert_result = mysqli_query($con, $query_insert_check);
        $numrows_insert = mysqli_num_rows($query_insert_result);

        // If no existing warehouse, insert the new warehouse
        if($numrows_insert == 0){
            $query = $Warehouse->insert($Warehouse);
            mysqli_query($con, $query);
            mysqli_close($con);
			$filteredPost = $_POST;
			unset($filteredPost['username'], $filteredPost['password']);
			writeLog("User ->" ." Warehouse added ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));

            echo "<script>window.location.href = '../Warehouse.php';</script>";
        } else {
            echo "Error : Warehouse ID already exists";
        }

    } else {
        // Password is incorrect
        echo "Error: Password is incorrect";
        return;
    }
} else {
    // Username not found
    echo "Error: Username does not exist";
    return;
}

?>
<?php require('Fullui.php'); ?>
