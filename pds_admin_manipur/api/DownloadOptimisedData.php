<?php

ini_set('memory_limit', '512M');
set_time_limit(120);

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = strtolower(trim($_GET['format']));

    $columns = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];
    $columns_pdf = ["scenario","from","from_id","from_name","from_district","from_lat","from_long","to","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];

    $month = "";
    $year = "";
    if (isset($_GET['month']) && $_GET['month'] != '') {
        $parts = explode('_', $_GET['month']);
        if (count($parts) >= 2) {
            $month = $parts[0];
            $year = $parts[1];
        }
    }

    $id = "";
    if ($month != "" && $year != "") {
        $query = "SELECT * FROM optimised_table WHERE month='$month' AND year='$year'";
        $result = mysqli_query($con, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $id = $row['id'];
        }
    }

    if (empty($id)) {
        $query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
        $result = mysqli_query($con, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $id = $row['id'];
            $month = $row['month'];
            $year = $row['year'];
        }
    }

    $tablename = "optimiseddata_" . $id;

    // Check if table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE '$tablename'");
    if (!$table_check || mysqli_num_rows($table_check) == 0) {
        die("Table does not exist");
    }

    $district = isset($_GET['district']) ? trim($_GET['district']) : '';
    $reviewed = isset($_GET['reviewed']) ? trim($_GET['reviewed']) : '';
    $approved = isset($_GET['approved']) ? trim($_GET['approved']) : '';
    $from_id = isset($_GET['fromid']) ? trim($_GET['fromid']) : '';
    $to_id = isset($_GET['toid']) ? trim($_GET['toid']) : '';

    $where = [];
    if (!empty($district) && strtolower($district) !== 'all') {
        $district_escaped = mysqli_real_escape_string($con, $district);
        $where[] = "to_district='$district_escaped'";
    }

    if ($reviewed == 'reviewed') {
        $where[] = "approve_district='yes'";
    } else if ($reviewed == 'notreviewed') {
        $where[] = "(approve_district='' OR approve_district IS NULL)";
    }

    if ($approved == 'approved') {
        $where[] = "approve_admin='yes'";
    } else if ($approved == 'notapproved') {
        $where[] = "(approve_admin='no' OR approve_admin='' OR approve_admin IS NULL)";
    }

    if (!empty($from_id)) {
        $from_id_escaped = mysqli_real_escape_string($con, $from_id);
        $where[] = "from_id='$from_id_escaped'";
    }

    if (!empty($to_id)) {
        $to_id_escaped = mysqli_real_escape_string($con, $to_id);
        $where[] = "to_id='$to_id_escaped'";
    }

    $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
    $query = "SELECT * FROM " . $tablename . $whereClause;
    $result = mysqli_query($con, $query);
    $numrows = $result ? mysqli_num_rows($result) : 0;

    // Pre-cache warehouses for lookup
    $warehouses = [];
    $res_wh = mysqli_query($con, "SELECT id, name, latitude, longitude, district FROM warehouse");
    if ($res_wh) {
        while ($wh = mysqli_fetch_assoc($res_wh)) {
            $warehouses[$wh['id']] = $wh;
        }
    }

    $tableData = array();
    $tableData_pdf = array();
    array_push($tableData, $columns);
    array_push($tableData_pdf, $columns_pdf);

    if ($numrows > 0) {
        while ($row = mysqli_fetch_array($result)) {
            if ($row['new_id_admin'] != null && $row['new_id_admin'] != "" && $row['new_id_admin'] != "0") {
                $new_id = $row['new_id_admin'];
                if (isset($warehouses[$new_id])) {
                    $row["from_lat"] = $warehouses[$new_id]['latitude'];
                    $row["from_long"] = $warehouses[$new_id]['longitude'];
                    $row["from_district"] = $warehouses[$new_id]['district'];
                } else {
                    $query_warehouse = "SELECT latitude, longitude, district FROM warehouse WHERE id='$new_id'";
                    $result_warehouse = mysqli_query($con, $query_warehouse);
                    if ($result_warehouse && mysqli_num_rows($result_warehouse) > 0) {
                        $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                        $warehouses[$new_id] = $row_warehouse;
                        $row["from_lat"] = $row_warehouse['latitude'];
                        $row["from_long"] = $row_warehouse['longitude'];
                        $row["from_district"] = $row_warehouse['district'];
                    }
                }
                $row["from_id"] = $row['new_id_admin'];
                $row["from_name"] = $row['new_name_admin'];
                $row["distance"] = $row['new_distance_admin'];
            } else if (($row['new_id_district'] != null && $row['new_id_district'] != "" && $row['new_id_district'] != "0") && ($row['approve_admin'] == "yes" || (isset($row['district_change_approve']) && $row['district_change_approve'] == "yes"))) {
                $new_id = $row['new_id_district'];
                if (isset($warehouses[$new_id])) {
                    $row["from_lat"] = $warehouses[$new_id]['latitude'];
                    $row["from_long"] = $warehouses[$new_id]['longitude'];
                    $row["from_district"] = $warehouses[$new_id]['district'];
                } else {
                    $query_warehouse = "SELECT latitude, longitude, district FROM warehouse WHERE id='$new_id'";
                    $result_warehouse = mysqli_query($con, $query_warehouse);
                    if ($result_warehouse && mysqli_num_rows($result_warehouse) > 0) {
                        $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                        $warehouses[$new_id] = $row_warehouse;
                        $row["from_lat"] = $row_warehouse['latitude'];
                        $row["from_long"] = $row_warehouse['longitude'];
                        $row["from_district"] = $row_warehouse['district'];
                    }
                }
                $row["from_id"] = $row['new_id_district'];
                $row["from_name"] = $row['new_name_district'];
                $row["distance"] = $row['new_distance_district'];
            }

            $temp = array();
            for ($i = 0; $i < count($columns); $i++) {
                array_push($temp, isset($row[$columns[$i]]) ? $row[$columns[$i]] : '');
            }

            $temp_pdf = array();
            for ($i = 0; $i < count($columns_pdf); $i++) {
                array_push($temp_pdf, isset($row[$columns_pdf[$i]]) ? $row[$columns_pdf[$i]] : '');
            }

            array_push($tableData, $temp);
            array_push($tableData_pdf, $temp_pdf);
        }
    }

    $filename = 'Optimise_Plan_Leg2_' . date('Y-m-d');

    function outputCSV($data) {
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    function addRow($pdf, $row, $colWidth, $isHeader = false) {
        global $originalColWidth;
        $pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
        $i = 0;
        foreach ($row as $col) {
            $i = $i + 1;
            if ($i == 10) {
                $colWidth = $originalColWidth * 3;
            } else {
                $colWidth = $originalColWidth;
            }
            $fontSize = 10;
            $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
            while ($pdf->GetStringWidth($col) > $colWidth - 2 && $fontSize > 1) {
                $fontSize -= 1;
                $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
            }
            $pdf->Cell($colWidth, 8, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();
    }

    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $rowIndex = 1;
            foreach ($tableData as $rowData) {
                $columnIndex = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    $columnIndex++;
                }
                $rowIndex++;
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');

            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 15);

            $pageWidth = $pdf->GetPageWidth() - 20; // Subtract margins (10 mm each side)
            $numCols = count($tableData_pdf[0]) + 2;
            $colWidth = $pageWidth / $numCols;
            $originalColWidth = $colWidth;

            addRow($pdf, $tableData_pdf[0], $colWidth, true);

            $rowHeight = 8;
            for ($i = 1; $i < count($tableData_pdf); $i++) {
                if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
                    $pdf->AddPage();
                    addRow($pdf, $tableData_pdf[0], $colWidth, true);
                }
                addRow($pdf, $tableData_pdf[$i], $colWidth);
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
    echo 'Error : Please specify a format in the GET request (e.g., ?format=csv).';
}

exit();
