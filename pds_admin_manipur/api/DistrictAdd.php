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


function formatName($name) {
    if(preg_match('/[^a-zA-Z\s]/', $name)){
        echo "Error : Name contains invalid characters. Only letters and spaces are allowed.";
		exit();
    }
    $name = ucwords(strtolower($name));
    return trim($name);
}

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

if ($row) {
if (password_verify($person->getPassword(), $row['password'])) {

$District = new District;
$District->setId(uniqid());
$District->setName(formatName($_POST['name']));

$query = $District->check($District);
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);
if($numrows>0){
	echo "Error : District name already exist";
	exit();
}
$query = $District->insert($District);
mysqli_query($con, $query);
mysqli_close($con);

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("User ->" ." District added ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));

echo "<script>window.location.href = '../District.php';</script>";

    } else {
        // Incorrect password
        echo "Error: Password is incorrect";
        return;
    }
} else {
    // Username not found
    echo "Error: Username does not exist";
    return;
}

?>
<?php require('Fullui.php');  ?>