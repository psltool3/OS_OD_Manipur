<?php
require('../util/Connection.php');

set_time_limit(600);
ini_set('memory_limit', '4G');

$district = isset($_POST['district']) ? $_POST['district'] : '';
$tablename = isset($_POST['tablename']) ? $_POST['tablename'] : '';
$tablename1 = isset($_POST['tablename1']) ? $_POST['tablename1'] : '';
$data = array();
$data1 = array();

if(empty($tablename) || $tablename == 'optimiseddata_leg1_'){
    $res_m = mysqli_query($con, "SELECT id FROM optimised_table_leg1 ORDER BY id DESC LIMIT 1");
    if($res_m && mysqli_num_rows($res_m) > 0){
        $row_m = mysqli_fetch_assoc($res_m);
        $tablename = "optimiseddata_leg1_".$row_m['id'];
        if(empty($tablename1) || $tablename1 == 'optimiseddata_leg1_'){
            $tablename1 = $tablename;
        }
    }
}

if(!empty($tablename)){
    $query = "SELECT * FROM ".$tablename." WHERE to_district='".$district."'";
    $result = mysqli_query($con, $query);
    if($result){
        while($row = mysqli_fetch_array($result))
        {
            if(!empty($row['new_id_admin'])){
                $id = $row['new_id_admin'];
                $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
                $result_warehouse = mysqli_query($con, $query_warehouse);
                if($result_warehouse && mysqli_num_rows($result_warehouse) != 0){
                    $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                    $row["from_lat"] = $row_warehouse['latitude'];
                    $row["from_long"] = $row_warehouse['longitude'];
                    $row["from_district"] = $row_warehouse['district'];
                }
                $row["from_id"] = $row['new_id_admin'];
                $row["from_name"] = $row['new_name_admin'];
                $row["distance"] = $row['new_distance_admin'];
            }
            else if(!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] == "yes"){
                $id = $row['new_id_district'];
                $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
                $result_warehouse = mysqli_query($con, $query_warehouse);
                if($result_warehouse && mysqli_num_rows($result_warehouse) != 0){
                    $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                    $row["from_lat"] = $row_warehouse['latitude'];
                    $row["from_long"] = $row_warehouse['longitude'];
                    $row["from_district"] = $row_warehouse['district'];
                }
                $row["from_id"] = $row['new_id_district'];
                $row["from_name"] = $row['new_name_district'];
                $row["distance"] = $row['new_distance_district'];
            }
            $data[] = $row;
        }
    }
}

if(!empty($tablename1) && $tablename != $tablename1){
    $query = "SELECT * FROM ".$tablename1." WHERE to_district='".$district."'";
    $result = mysqli_query($con, $query);
    if($result){
        while($row = mysqli_fetch_array($result))
        {
            if(!empty($row['new_id_admin'])){
                $id = $row['new_id_admin'];
                $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
                $result_warehouse = mysqli_query($con, $query_warehouse);
                if($result_warehouse && mysqli_num_rows($result_warehouse) != 0){
                    $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                    $row["from_lat"] = $row_warehouse['latitude'];
                    $row["from_long"] = $row_warehouse['longitude'];
                    $row["from_district"] = $row_warehouse['district'];
                }
                $row["from_id"] = $row['new_id_admin'];
                $row["from_name"] = $row['new_name_admin'];
                $row["distance"] = $row['new_distance_admin'];
            }
            else if(!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] == "yes"){
                $id = $row['new_id_district'];
                $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
                $result_warehouse = mysqli_query($con, $query_warehouse);
                if($result_warehouse && mysqli_num_rows($result_warehouse) != 0){
                    $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                    $row["from_lat"] = $row_warehouse['latitude'];
                    $row["from_long"] = $row_warehouse['longitude'];
                    $row["from_district"] = $row_warehouse['district'];
                }
                $row["from_id"] = $row['new_id_district'];
                $row["from_name"] = $row['new_name_district'];
                $row["distance"] = $row['new_distance_district'];
            }
            $data1[] = $row;
        }
    }
}

$resultarray = array();
$resultarray["data"] = $data;
$resultarray["data1"] = $data1;

echo json_encode($resultarray);
?>
