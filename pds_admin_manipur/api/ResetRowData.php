<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php');

if(!SessionCheck()){
    echo json_encode(array("status" => "error", "message" => "Unauthorized"));
    return;
}

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$id = "";
while($row = mysqli_fetch_array($result))
{
	$id= $row["id"];
}

$tablename = "optimiseddata_".$id;

if(isset($_POST['from_id']) && isset($_POST['to_id']) && isset($_POST['commodity'])){
    $fromid = mysqli_real_escape_string($con, $_POST['from_id']);
    $toid = mysqli_real_escape_string($con, $_POST['to_id']);
    $commodity = mysqli_real_escape_string($con, $_POST['commodity']);
    
    // Reset admin approval and override fields back to NULL
    $query = "UPDATE " . $tablename . " SET 
              approve_admin=NULL,
              district_change_approve=NULL,
              new_id_admin=NULL,
              new_name_admin=NULL,
              new_distance_admin=NULL,
              reason_admin=NULL
              WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
              
    if(mysqli_query($con, $query)){
        writeLog("Admin User -> Reset Row Data | " . $_SESSION['user'] . " | " . $fromid . " - " . $toid . " - " . $commodity);
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => mysqli_error($con)));
    }
} else {
    echo json_encode(array("status" => "error", "message" => "Invalid parameters"));
}
mysqli_close($con);
?>
