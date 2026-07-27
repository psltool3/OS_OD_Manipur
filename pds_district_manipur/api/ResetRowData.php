<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('../util/Logger.php');

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
    
    // Check if admin has already approved/reviewed
    $check_query = "SELECT approve_admin, district_change_approve FROM " . $tablename . " WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
    $check_result = mysqli_query($con, $check_query);
    if($check_row = mysqli_fetch_assoc($check_result)){
        if(!empty($check_row['approve_admin']) || !empty($check_row['district_change_approve'])){
            echo json_encode(array("status" => "error", "message" => "Cannot reset: Admin has already approved/reviewed this row."));
            exit();
        }
    }

    // Reset all district suggestion fields back to NULL
    $query = "UPDATE " . $tablename . " SET 
              approve_district=NULL, 
              new_id_district=NULL, 
              new_name_district=NULL, 
              new_distance_district=NULL, 
              reason_district=NULL 
              WHERE from_id='$fromid' AND to_id='$toid' AND commodity='$commodity'";
              
    if(mysqli_query($con, $query)){
        writeLog("District User -> Reset Row Data | " . $_SESSION['district_user'] . " | " . $fromid . " - " . $toid . " - " . $commodity);
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => mysqli_error($con)));
    }
} else {
    echo json_encode(array("status" => "error", "message" => "Invalid parameters"));
}
mysqli_close($con);
?>
