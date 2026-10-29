<?php

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    $headers = ["District", "Name of FCI Warehouse", "FCI ID", "Warehouse Type", "Latitude", "Longitude", "Demand (Qtl)"];
    $tablename = isset($_GET['tableName']) ? $_GET['tableName'] : '';
    if (empty($tablename) || $tablename == 'fci_leg1_') {
        $res_m = mysqli_query($con, "SELECT id FROM optimised_table_leg1 ORDER BY last_updated DESC LIMIT 1");
        if ($res_m && mysqli_num_rows($res_m) > 0) {
            $row_m = mysqli_fetch_assoc($res_m);
            $tablename = "fci_leg1_".$row_m['id'];
        }
    }
	
	$tableData = array();
    array_push($tableData, $headers);

	$query = "SELECT * FROM ".$tablename." WHERE 1";
    $result = mysqli_query($con, $query);
    if($result) {
        $numrows = mysqli_num_rows($result);
        if($numrows > 0){
            while($row = mysqli_fetch_array($result)){
                $district = isset($row['district']) ? $row['district'] : '';
                $name = isset($row['name']) ? $row['name'] : '';
                $id_val = isset($row['id']) ? $row['id'] : '';
                $type = isset($row['type']) && $row['type'] !== '' ? $row['type'] : (isset($row['warehousetype']) ? $row['warehousetype'] : '');
                $lat = isset($row['latitude']) ? $row['latitude'] : '';
                $lng = isset($row['longitude']) ? $row['longitude'] : '';
                $demand = isset($row['demand']) && $row['demand'] !== '' ? $row['demand'] : (isset($row['storage']) && $row['storage'] !== '' ? $row['storage'] : (isset($row['capacity']) ? $row['capacity'] : ''));

                $temp = [$district, $name, $id_val, $type, $lat, $lng, $demand];
                array_push($tableData, $temp);
            }
        }
    }
	
    // Filename for the downloaded file
    $filename = 'fci_leg1_data';

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

            // Insert data tableData (includes headers at row 1)
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
