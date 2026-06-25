<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if (!SessionCheck()) {
    return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

// Check if the logged-in username matches the session username
if ($_SESSION['user'] != $person->getUsername()) {
    echo "User is logged in with a different username and password";
    return;
}

// Fetch the stored hashed password for verification
$query = "SELECT * FROM login WHERE username='" . mysqli_real_escape_string($con, $person->getUsername()) . "'";
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

$uid = $_POST["uid"];

if ($uid != "all") {
    $to = $uid; // Assuming $uid is an email address
    $subject = "Test email";
    $message = "This is a test email sent using the mail function in PHP.";
    $headers = "From: " . $_SESSION['user'];

    $mailSent = mail($to, $subject, $message, $headers);

    if ($mailSent) {
        echo "Email sent successfully!";
    } else {
        echo "Error: Unable to send email.";
    }
} else {
    // Send emails to all users
    $query = "SELECT username FROM login WHERE role != 'admin'"; // Modify as needed
    $result = mysqli_query($con, $query);
    while ($rows = mysqli_fetch_array($result)) {
        $to = $rows['username']; // Assuming usernames are email addresses
        $subject = "Test email";
        $message = "This is a test email sent using the mail function in PHP.";
        $headers = "From: " . $_SESSION['user'];

        $mailSent = mail($to, $subject, $message, $headers);

        if ($mailSent) {
            echo "Email sent successfully to $to!";
        } else {
            echo "Error: Unable to send email to $to.";
        }
    }
}

mysqli_close($con);
echo "<script>window.location.href = '../SendEmail.php';</script>";
?>
<?php require('Fullui.php'); ?>
