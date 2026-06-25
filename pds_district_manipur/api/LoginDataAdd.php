<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');

if (!SessionCheck()) {
    return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

// Check if the logged-in username matches the session username
if ($_SESSION['district_user'] != $person->getUsername()) {
    echo "User is logged in with different username and password";
    return;
}

// Fetch the stored hashed password for verification
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

// Proceed to create a new user account
$newPerson = new Login;
$newPerson->setUsername($_POST["newusername"]);
$newPerson->setPassword(password_hash($_POST["newpassword"], PASSWORD_DEFAULT)); // Hash the new password
$newPerson->setRole($_POST["district"]);
$uid = uniqid();

$query = "SELECT * FROM login WHERE username='" . $newPerson->getUsername() . "'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);

if ($numrows == 1) {
    echo "Error : Username already exists";
} else if ($numrows == 0) {
    // Insert the new user with the hashed password
    $query1 = "INSERT INTO login (username, password, uid, role, verified) VALUES ('" . $newPerson->getUsername() . "','" . $newPerson->getPassword() . "','$uid','" . strtolower($newPerson->getRole()) . "','0')";
    mysqli_query($con, $query1);

    mysqli_close($con);
    echo "<script>window.location.href = '../Userdata.php';</script>";
}
?>
<?php require('Fullui.php'); ?>
