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
// Input sanitization for district and status
    $district = mysqli_real_escape_string($con, $_POST["district"]);
    $status = mysqli_real_escape_string($con, $_POST["status"]);

    // Update the 'active' status of the warehouse based on the provided status
    if($status == 'active'){
        $query = "UPDATE warehouse SET active='1' WHERE district=?";
        writeLog("User ->" ." Warehouse Active -> ". $_SESSION['user'] . "| " . $district);
    } else {
        $query = "UPDATE warehouse SET active='0' WHERE district=?";
        writeLog("User ->" ." Warehouse InActive -> ". $_SESSION['user'] . "| " . $district);
    }

    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $district);
    mysqli_stmt_execute($stmt);

    echo "<script>window.location.href = '../Warehouse.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}



?>

<?php require('Fullui.php'); ?>
