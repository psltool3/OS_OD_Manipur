<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require 'vendor/autoload.php';
require('api/fpdf/fpdf.php');

$columns_pdf = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","status"];

$filename = 'Optimised_Plan_Leg1';

$id = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($id)) {
	$res_latest = mysqli_query($con, "SELECT id FROM optimised_table_leg1 ORDER BY id DESC LIMIT 1");
	if ($res_latest && mysqli_num_rows($res_latest) > 0) {
		$row_latest = mysqli_fetch_assoc($res_latest);
		$id = (string)$row_latest['id'];
	}
}
$tablename = "optimiseddata_leg1_".$id;

$month = "";
$date = "";
$cost = "";

$query = "SELECT * FROM optimised_table_leg1 WHERE id='$id'";
$result = mysqli_query($con, $query);
if($result && mysqli_num_rows($result) > 0){
	while($row = mysqli_fetch_assoc($result)){
		$month = isset($row["month"]) ? $row["month"] : '';
		$date = isset($row["last_updated"]) ? $row["last_updated"] : '';
		$cost = isset($row["cost"]) ? $row["cost"] : '';
	}
}

$allocation = 0;
$qkm = 0;
$qkm_optimised = 0;
$averagedistanceoptimised = 0;

$query = "SELECT * FROM $tablename WHERE 1";
$result = mysqli_query($con, $query);
$data = array();

if($result && mysqli_num_rows($result) > 0){
	while($row = mysqli_fetch_assoc($result))
	{		
		$qty = isset($row["quantity"]) ? (float)$row["quantity"] : 0;
		$dist = isset($row["distance"]) ? (float)$row["distance"] : 0;
		$qkm_optimised += $qty * $dist;

		if(!empty($row['new_id_admin'])){
			$row["distance"] = $row['new_distance_admin'];
		}
		else if(!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] == "yes"){
			$row["distance"] = $row['new_distance_district'];
		}		
		
		$final_dist = isset($row["distance"]) ? (float)$row["distance"] : 0;
		$allocation += $qty;
		$qkm += $qty * $final_dist;
		$data[] = $row;
	}
}

if($allocation > 0){
	$averagedistanceoptimised = round($qkm_optimised / $allocation, 2);
}
$qkm = round($qkm, 2);

$tableData_pdf = array();
array_push($tableData_pdf, $columns_pdf);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 15);

$pageWidth = $pdf->GetPageWidth() - 20;
$numCols = count($tableData_pdf[0]) + 2;
$colWidth = $pageWidth / $numCols;
$originalColWidth = $colWidth;

function addRow($pdf, $row, $colWidth, $isHeader = false) {
	global $originalColWidth;
	$pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
	$i = 0;
	foreach ($row as $col) {
		$i++;
		if($i == 10){
			$cW = $colWidth * 3;
		} else {
			$cW = $originalColWidth;
		}
		$fontSize = 12;
		$pdf->SetFont('Arial', 'B', $fontSize);
		while ($pdf->GetStringWidth((string)$col) > $cW - 2 && $fontSize > 1) {
			$fontSize -= 1;
			$pdf->SetFont('Arial', 'B', $fontSize);
		}
		$pdf->Cell($cW, 10, (string)$col, 1, 0, 'C', true);
	}
	$pdf->Ln();
}

$fontSize = 12;
$pdf->SetFont('Arial', 'B', $fontSize);
$text = "PDS report generated for Leg 1 state Manipur and applicable month ".ucfirst($month)." and Date ".$date;
$pdf->Cell(0, 10, $text, 0, 1);

$text = "Cost saving for Leg 1";
$pdf->Cell(0, 10, $text, 0, 1);

$pdf->Cell(40, 10, 'Qkm', 1);
$pdf->Cell(40, 10, 'Allocation', 1);
$pdf->Cell(50, 10, 'Average Distance', 1);
$pdf->Cell(40, 10, 'Cost', 1);
$pdf->Ln();

$pdf->Cell(40, 10, $qkm, 1);
$pdf->Cell(40, 10, $allocation, 1);
$pdf->Cell(50, 10, $averagedistanceoptimised, 1);
$pdf->Cell(40, 10, $cost, 1);
$pdf->Ln();
$pdf->Ln();

addRow($pdf, $tableData_pdf[0], $colWidth, true);

$rowHeight = 10;

if(!empty($data)){
	for ($i = 0; $i < count($data); $i++) {
		if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
			$pdf->AddPage();
			addRow($pdf, $tableData_pdf[0], $colWidth, true);
		}
		$temp = array();
		foreach ($columns_pdf as $colKey) {
			$temp[$colKey] = isset($data[$i][$colKey]) ? $data[$i][$colKey] : '';
		}
		addRow($pdf, $temp, $colWidth);
	}
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
echo $pdf->Output('S');
?>
