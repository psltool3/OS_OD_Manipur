<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');

function generateRandomId($length = 10) {
    // Generate random bytes
    $bytes = random_bytes(ceil($length / 2));
    
    // Convert random bytes to hexadecimal string
    $randomId = substr(bin2hex($bytes), 0, $length);

    return $randomId;
}

if (!SessionCheck()) {
    return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

if ($_SESSION['user'] != $person->getUsername()) {
    echo "User is logged in with different username and password";
    return;
}

// Fetch user details by username
$query = "SELECT * FROM login WHERE username='" . $person->getUsername() . "'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);

if ($numrows == 0) {
    echo "Error : Username is incorrect";
    return;
}

// Get the stored hashed password
$row = mysqli_fetch_assoc($result);
$hashedPassword = $row['password'];

// Verify the input password with the hashed password
if (!password_verify($person->getPassword(), $hashedPassword)) {
    echo "Error : Password is incorrect";
    return;
}

$message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
$uniqueid = htmlspecialchars($_POST['uniqueid'], ENT_QUOTES, 'UTF-8');

if (!preg_match('/^[a-zA-Z0-9\s,\.]+$/', $message)) {
    echo "Alert: Message contains invalid characters. Only letters, numbers, spaces, commas are allowed.";
    return;
}

$date = date('Y-m-d H:i:s');

if ($uniqueid == "all") {
    $query = "SELECT uid FROM login WHERE role != 'admin'";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $uniqueid = $row['uid'];
        $id = generateRandomId(10);
        $query = "INSERT INTO user_message (id, user_id, message, date, acknowledged) VALUES ('$id', '$uniqueid', '$message', '$date', 'no')";
        mysqli_query($con, $query);
		
		$log_query = "select username from login WHERE uid='$uniqueid'";
		$log_result = mysqli_query($con,$log_query);
		if ($log_result && $row = $log_result->fetch_assoc()) {
		$log_name =  $row['username'];
		}

		$filteredPost = $_POST;
		unset($filteredPost['username'], $filteredPost['password']);
		writeLog("User ->" ." Send  Message ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost). " | " . $log_name);

    }
} else {
    $id = generateRandomId(10);
    $query = "INSERT INTO user_message (id, user_id, message, date, acknowledged) VALUES ('$id', '$uniqueid', '$message', '$date', 'no')";
    mysqli_query($con, $query);
}

$log_query = "select username from login WHERE uid='$uniqueid'";
$log_result = mysqli_query($con,$log_query);
if ($log_result && $row = $log_result->fetch_assoc()) {
	$log_name =  $row['username'];
}

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("User ->" ." Send  Message ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost). " | " . $log_name);

echo "<script>window.location.href = '../SendMessage.php';</script>";

?>
<?php require('Fullui.php'); ?>
