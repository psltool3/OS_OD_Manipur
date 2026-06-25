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

require('Header.php');

// Initialize the Login object and set username and password
$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

// Ensure that the session user matches the provided username
if($_SESSION['user'] != $person->getUsername()){
    echo "User is logged in with a different username and password";
    return;
}

// Query to fetch the user details based on the username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

$dbHashedPassword = $row['password'];
if (password_verify($person->getPassword(), $row['password'])) {
    
    $Warehouse = new Warehouse;
    $Warehouse->setUniqueid($_POST['uid']);

    // Generate the delete query based on the UID provided
    $query = $Warehouse->delete($Warehouse);

    // If 'all' is passed as the UID, delete all warehouses
    if($_POST['uid'] == "all"){
        $query = $Warehouse->deleteall($Warehouse);
    }
    
    $log_query = $Warehouse->logname($Warehouse);
    $log_name= "all";
    $log_result = mysqli_query($con,$log_query);
    if ($log_result && $row = $log_result->fetch_assoc()) {
            $log_name =  $row['name'];
    }
        
        $filteredPost = $_POST;
    unset($filteredPost['username'], $filteredPost['password']);
    writeLog("User ->" ." Warehouse deleted -> ". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost) . " | " . $log_name);

    // Execute the delete query and close the database connection
    mysqli_query($con, $query);
    mysqli_close($con);

    // Redirect to the warehouse page after successful deletion
    echo "<script>window.location.href = '../Warehouse.php';</script>";
} else {
    // If password is incorrect
    echo "Error: Password is incorrect";
    return;
}


?>
<?php require('Fullui.php'); ?>
