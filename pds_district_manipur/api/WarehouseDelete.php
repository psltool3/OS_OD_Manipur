<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');
require('../util/Security.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';
if(!SessionCheck()){
	return;
}

$district = $_SESSION["district_district"];

require('Header.php');

// Create a new Login object
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Check if the session username matches the posted username
if ($_SESSION['district_user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password";
    return;
}

// Fetch user data based on the username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

$hashedPassword = $row['password'];

// Verify the hashed password
if (!password_verify($person->getPassword(), $hashedPassword)) {
    echo "Error: Password or Username is incorrect";
    return;
}

// Create a Warehouse object
$Warehouse = new Warehouse;
$Warehouse->setUniqueid($_POST['uid']);

// Check if the "uid" field is "all" and delete all warehouses in the district
if ($_POST['uid'] == "all") {
    $query = $Warehouse->deletealldistrict($Warehouse, $district);
} else {
    $query = $Warehouse->delete($Warehouse);
}

$log_query = $Warehouse->logname($Warehouse);
$log_name= "all";
$log_result = mysqli_query($con,$log_query);
if ($log_result && $row = $log_result->fetch_assoc()) {
		$log_name =  $row['name'];
}

// Execute the delete query
mysqli_query($con, $query);
mysqli_close($con);

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("District User ->" ." Warehouse deleted -> ". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost) . " | " . $log_name);

// Redirect to the Warehouse page
echo "<script>window.location.href = '../Warehouse.php';</script>";

?>
<?php require('Fullui.php'); ?>
