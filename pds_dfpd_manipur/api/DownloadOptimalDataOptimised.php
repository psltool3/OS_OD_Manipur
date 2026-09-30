<?php

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    $columns = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];
	
    $tablename = isset($_GET['tableName']) ? $_GET['tableName'] : '';
    $district = isset($_GET['district']) ? $_GET['district'] : '';

    if (empty($tablename) || $tablename == 'optimiseddata_') {
        $res_m = mysqli_query($con, "SELECT id FROM optimised_table ORDER BY id DESC LIMIT 1");
        if ($res_m && mysqli_num_rows($res_m) > 0) {
            $row_m = mysqli_fetch_assoc($res_m);
            $tablename = "optimiseddata_".$row_m['id'];
        }
    }
    
	$tableData = array();
    array_push($tableData,$columns);

    if (!empty($tablename)) {
        $query = "SELECT * FROM ".$tablename." WHERE 1";
        if (!empty($district) && $district != "all") {
            $district_escaped = mysqli_real_escape_string($con, $district);
            $query = "SELECT * FROM ".$tablename." WHERE to_district='".$district_escaped."'";
        }
        $result = mysqli_query($con,$query);
        $numrows = $result ? mysqli_num_rows($result) : 0;
        
        if($numrows>0){
            while($row = mysqli_fetch_array($result)){
                if(isset($row['new_id_admin']) && $row['new_id_admin']!=null && $row['new_id_admin']!="" && $row['new_id_admin']!="0"){
                    $new_id = $row['new_id_admin'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
                    $result_warehouse = mysqli_query($con,$query_warehouse);
                    $numrows_warehouse = $result_warehouse ? mysqli_num_rows($result_warehouse) : 0;
                    if($numrows_warehouse!=0){
                        $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                        $row["from_lat"] = $row_warehouse['latitude'];
                        $row["from_long"] = $row_warehouse['longitude'];
                        $row["from_district"] = $row_warehouse['district'];
                    }
                    $row["from_id"] = $row['new_id_admin'];
                    $row["from_name"] = $row['new_name_admin'];
                    $row["distance"] = $row['new_distance_admin'];
                }
                else if(isset($row['new_id_district']) && ($row['new_id_district']!=null && $row['new_id_district']!="" && $row['new_id_district']!="0") && (isset($row['approve_admin']) && $row['approve_admin']=="yes" || isset($row['admin_approve']) && $row['admin_approve']=="yes")){
                    $new_id = $row['new_id_district'];
                    $query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$new_id'";
                    $result_warehouse = mysqli_query($con,$query_warehouse);
                    $numrows_warehouse = $result_warehouse ? mysqli_num_rows($result_warehouse) : 0;
                    if($numrows_warehouse!=0){
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
                for($i=0;$i<count($columns);$i++){
                   array_push($temp, isset($row[$columns[$i]]) ? $row[$columns[$i]] : '');
                }
                array_push($tableData,$temp);
            }
        }
    }
	
	
    // Filename for the downloaded file
    $filename = 'table_data';

    // Set headers for the chosen format
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            // Create a new PhpSpreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set column names as the first row
            /*$columnIndex = 1;
            foreach ($columns as $columnName) {
                $sheet->setCellValueByColumnAndRow($columnIndex, 1, $columnName);
                $columnIndex++;
            }*/

            // Insert data tableData
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
            require('fpdf/fpdf.php');

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 0);

            // Highlight the first row as header
            $pdf->SetFillColor(200, 220, 255); // Set background color
            $pdf->SetTextColor(0); // Reset text color
            $case = 0;
            foreach ($tableData as $row) {
                foreach ($row as $col) {
                    $pdf->Cell(30, 10, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 255); 
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            echo $pdf->Output('S');
            break;

        default:
            echo 'Error : Invalid format specified.';
            break;
    }
} else {
    echo 'Error : Please specify a format in the GET request (e.g., ?format=pdf).';
}



// Function to output CSV data
function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();