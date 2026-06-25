<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
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

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Create Login object and set credentials
$person = new Login;
$person->setUsername(sanitizeInput($_POST["username"]));
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Check if the current session user matches the one trying to perform the action
if ($_SESSION['user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password.";
    return;
}


$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){

    $district = sanitizeInput($_POST["district"]);
    $status = sanitizeInput($_POST["status"]);
    $fpstype = sanitizeInput($_POST["fpstype"]);
    
    // Determine the active status value and action
    $activeValue = ($status == 'active') ? '1' : '0';
    $action = ($status == 'active') ? "Active" : "InActive";
    
    // Prepare the SQL query based on FPS type
    if ($fpstype == 'Model FPS') {
        $query = "UPDATE fps SET active=? WHERE district=? AND type='Model FPS'";
        writeLog("User -> Model FPS $action -> " . $_SESSION['user'] . " | " . $district);
    } else {
        $query = "UPDATE fps SET active=? WHERE district=? AND type='Normal FPS'";
        writeLog("User -> Normal FPS $action -> " . $_SESSION['user'] . " | " . $district);
    }
        
    echo "<script>window.location.href = '../FPS.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}


?>

<?php require('Fullui.php'); ?>
