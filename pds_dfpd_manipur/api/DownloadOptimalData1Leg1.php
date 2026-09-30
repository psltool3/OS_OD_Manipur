<?php

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    $columns = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];
	
    $tablename = isset($_GET['tableName']) ? $_GET['tableName'] : '';
    $tablename1 = isset($_GET['tableName1']) ? $_GET['tableName1'] : '';
    $district = isset($_GET['district']) ? $_GET['district'] : '';

    if (empty($tablename) && isset($_GET['month'])) {
        $month_param = $_GET['month'];
        $parts = explode('_', $month_param);
        $m_name = isset($parts[0]) ? $parts[0] : '';
        $m_year = isset($parts[1]) ? $parts[1] : '';
        $query_m = "SELECT * FROM optimised_table_leg1 WHERE month='$m_name' AND year='$m_year'";
        $res_m = mysqli_query($con, $query_m);
        if ($res_m && mysqli_num_rows($res_m) > 0) {
            $row_m = mysqli_fetch_assoc($res_m);
            $tablename = "optimiseddata_leg1_".$row_m['id'];
            $tablename1 = $tablename;
        }
    }

    if (empty($tablename) || $tablename == 'optimiseddata_leg1_') {
        $res_m = mysqli_query($con, "SELECT id FROM optimised_table_leg1 ORDER BY id DESC LIMIT 1");
        if ($res_m && mysqli_num_rows($res_m) > 0) {
            $row_m = mysqli_fetch_assoc($res_m);
            $tablename = "optimiseddata_leg1_".$row_m['id'];
            if (empty($tablename1) || $tablename1 == 'optimiseddata_leg1_') {
                $tablename1 = $tablename;
            }
        }
    }

	$tableData = array();
    array_push($tableData, $columns);

    if (!empty($tablename)) {
        $query = "SELECT * FROM ".$tablename." WHERE 1";
        if (!empty($district) && $district != "all") {
            $district_escaped = mysqli_real_escape_string($con, $district);
            $query = "SELECT * FROM ".$tablename." WHERE to_district='".$district_escaped."'";
        }
        $result = mysqli_query($con, $query);
        $numrows = $result ? mysqli_num_rows($result) : 0;
        
        if($numrows > 0){
            while($row = mysqli_fetch_array($result)){
                if(isset($row['new_id_admin']) && !empty($row['new_id_admin'])){
                    $new_id = $row['new_id_admin'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
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
                else if(isset($row['new_id_district']) && !empty($row['new_id_district']) && (isset($row['approve_admin']) && $row['approve_admin']=="yes" || isset($row['admin_approve']) && $row['admin_approve']=="yes")){
                    $new_id = $row['new_id_district'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
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
                $temp = array();
                for($i=0; $i<count($columns); $i++){
                    $colKey = $columns[$i];
                    array_push($temp, isset($row[$colKey]) ? $row[$colKey] : '');
                }
                array_push($tableData, $temp);
            }
        }
    }

    if (!empty($tablename1) && $tablename1 != $tablename) {
        $query1 = "SELECT * FROM ".$tablename1." WHERE 1";
        if (!empty($district) && $district != "all") {
            $district_escaped = mysqli_real_escape_string($con, $district);
            $query1 = "SELECT * FROM ".$tablename1." WHERE to_district='".$district_escaped."'";
        }
        $result1 = mysqli_query($con, $query1);
        $numrows1 = $result1 ? mysqli_num_rows($result1) : 0;
        
        if($numrows1 > 0){
            while($row = mysqli_fetch_array($result1)){
                if(isset($row['new_id_admin']) && !empty($row['new_id_admin'])){
                    $new_id = $row['new_id_admin'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
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
                else if(isset($row['new_id_district']) && !empty($row['new_id_district']) && (isset($row['approve_admin']) && $row['approve_admin']=="yes" || isset($row['admin_approve']) && $row['admin_approve']=="yes")){
                    $new_id = $row['new_id_district'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
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
                $temp = array();
                for($i=0; $i<count($columns); $i++){
                    $colKey = $columns[$i];
                    array_push($temp, isset($row[$colKey]) ? $row[$colKey] : '');
                }
                array_push($tableData, $temp);
            }
        }
    }
    
    $filename = 'optimised_data_leg1';

    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $columnIndex = 1;
            foreach ($columns as $columnName) {
                $sheet->setCellValueByColumnAndRow($columnIndex, 1, $columnName);
                $columnIndex++;
            }

            $rowIndex = 1;
            foreach ($tableData as $rowData) {
                $columnIndex = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    $columnIndex++;
                }
                $rowIndex++;
            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            if (file_exists('fpdf/fpdf.php')) {
                require('fpdf/fpdf.php');
            } else if (file_exists('../fpdf/fpdf.php')) {
                require('../fpdf/fpdf.php');
            }

            if (class_exists('FPDF')) {
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 0);
                $pdf->SetFillColor(200, 220, 255);
                $pdf->SetTextColor(0);
                $pdf->SetFont('helvetica', '', 7);
                foreach ($tableData as $row) {
                    foreach ($row as $col) {
                        $pdf->Cell(22, 5, $col, 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFillColor(255, 255, 255); 
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                echo $pdf->Output('S');
            } else {
                echo 'PDF library not found.';
            }
            break;

        default:
            echo 'Error : Invalid format specified.';
            break;
    }
} else {
    echo 'Error : Please specify a format in the GET request (e.g., ?format=csv).';
}

function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}
