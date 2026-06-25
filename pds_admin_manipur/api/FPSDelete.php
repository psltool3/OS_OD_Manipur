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
if(!SessionCheck()){
    return;
}

require('Header.php');

// Create Login object and set credentials
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Ensure the current session user matches the one trying to perform the action
if($_SESSION['user'] != $person->getUsername()){
    echo "User is logged in with a different username and password.";
    return;
}

// Use prepared statements to prevent SQL injection
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);


$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    $FPS = new FPS;
    $uniqueId = mysqli_real_escape_string($con, $_POST['uid']);
    $FPS->setUniqueid($uniqueId);
    
    // Prepare query based on the unique ID
    if($uniqueId == "all"){
        $query = $FPS->deleteall($FPS);
    } else {
        $query = $FPS->delete($FPS);
    }
    
    $log_query = $FPS->logname($FPS);
    $log_name= "all";
    $log_result = mysqli_query($con,$log_query);
    if ($log_result && $row = $log_result->fetch_assoc()) {
        $log_name =  $row['name'];
    }
    
    // Execute the query
    mysqli_query($con, $query);
    
    // Close the database connection
    mysqli_close($con);
    
    $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("User ->" ." FPS deleted -> ". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost) . " | " . $log_name);
    
    // Redirect to FPS.php
    echo "<script>window.location.href = '../FPS.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}


?>

<?php require('Fullui.php'); ?>
