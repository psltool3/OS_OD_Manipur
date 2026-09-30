<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	//return;
}
$message = "";
if (!isset($_POST['month'])) {
    die(json_encode(["message" => "Missing Month Parameter"]));
}

$month = isset($_POST['month']) ? $_POST['month'] : '';
$year = isset($_POST['year']) ? $_POST['year'] : '';

if (!is_string($month)) {
    die(json_encode(["message" => "Invalid Month Parameter"]));
}

$valid_months = ['jan', 'feb', 'march', 'april', 'may', 'june', 'july', 'aug', 'sept', 'oct', 'nov', 'dec'];
if (!in_array(strtolower($month), $valid_months)) {
    die(json_encode(["message" => "Invalid Month Parameter"]));
}
$month = mysqli_real_escape_string($con, $month);
$year = mysqli_real_escape_string($con, $year);

$month_index = array_search(strtolower($month), $valid_months);
$prev_month = '';
$prev_year = '';
if ($month_index !== false) {
    $prev_month_index = $month_index - 1;
    $prev_year = intval($year);
    if ($prev_month_index < 0) {
        $prev_month_index = 11;
        $prev_year = $prev_year - 1;
    }
    $prev_month = $valid_months[$prev_month_index];
}

$query = "SELECT * FROM optimised_table WHERE month='$month' AND year='$year'";
$result = mysqli_query($con,$query);
$response = array();
$response_data = array();
while($row = mysqli_fetch_array($result))
{
	$temp = array();
	$temp["year"] = $row["year"];
	$temp["month"] = $row["month"];
	$temp["id"] = $row["id"];
	$temp["applicable"] = $row["applicable"];
	$temp["last_updated"] = $row["last_updated"];
	array_push($response,$temp);
	$query_count = "SELECT * FROM optimiseddata_".$row["id"]." WHERE approve_admin<>'yes' OR approve_admin IS NULL";
	$result_count = mysqli_query($con,$query_count);
	$numrows_count = mysqli_num_rows($result_count);
	if($numrows_count!=0){
		$message = "Please approve all tags of leg2 first";
	}
}

if(count($response)==0 && !empty($prev_month)){
    $query = "SELECT * FROM optimised_table WHERE month='$prev_month' AND year='$prev_year'";
    $result = mysqli_query($con,$query);
    while($row = mysqli_fetch_array($result))
    {
        $temp = array();
        $temp["year"] = $row["year"];
        $temp["month"] = $row["month"];
        $temp["id"] = $row["id"];
        $temp["applicable"] = $row["applicable"];
        $temp["last_updated"] = $row["last_updated"];
        array_push($response,$temp);
        $query_approve = "SELECT * FROM optimiseddata_".$row["id"]." WHERE approve_admin<>'yes' OR approve_admin IS NULL";
        $result_approve = mysqli_query($con,$query_approve);
        $numrows_approve = mysqli_num_rows($result_approve);
        if($numrows_approve != 0){
            $message = "Please approve all tags of leg2 first";
        }
    }
}

if(count($response)==0){
	$message = "First optimized the leg2 for this month or previous month";
}
$response_data["data"] = $response;
$response_data["message"] = $message;
echo json_encode($response_data);

?>