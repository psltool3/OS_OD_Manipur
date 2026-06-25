<?php

require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');

if(!SessionCheck()){
	return;
}

require('Header.php');


$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

// Query the database to get the hashed password for the username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

// Check if the username exists and verify the password using password_verify
if ($row) {
    // Use password_verify to compare the entered password with the hashed password
    if (password_verify($person->getPassword(), $row['password'])) {
        // Password is correct, proceed with the delete operation
        $District = new District;
        $District->setId($_POST['uid']);

$query = $District->delete($District);

$log_query = $District->logname($District);
$log_result = mysqli_query($con,$log_query);
if ($log_result && $row = $log_result->fetch_assoc()) {
		$log_name =  $row['name'];
}


mysqli_query($con,$query);
mysqli_close($con);

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("User ->" ." District deleted -> ". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost) . " | " . $log_name);


echo "<script>window.location.href = '../District.php';</script>";

    } else {
        echo "Error: Password is incorrect";
        return;
    }
} else {
    echo "Error: Username does not exist";
    return;
}

?>
<?php require('Fullui.php');  ?>