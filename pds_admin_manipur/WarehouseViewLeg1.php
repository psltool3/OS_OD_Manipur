<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

$id = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($id)) {
	$res_latest = mysqli_query($con, "SELECT id FROM optimised_table_leg1 ORDER BY id DESC LIMIT 1");
	if ($res_latest && mysqli_num_rows($res_latest) > 0) {
		$row_latest = mysqli_fetch_assoc($res_latest);
		$id = (string)$row_latest['id'];
	}
}
$tablename = "warehouse_leg1_".$id;

?>
<style>
     td {
            font-size: 16px; /* Increase font size for table headers and data cells */
        }
        .table thead tr th {
    background-color: #95b75d !important;
    /* border: 2px solid #777; */
    color: black;
    /* Optional: Font size for table header */
}
    </style>

                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="OptimisedDataAllLeg1.php">Home</a></li>
                    <li class="active">Warehouse View Leg 1</li>
                </ul>
                <!-- END BREADCRUMB -->


				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
							<div class="panel-heading">
                                    <h3 class="panel-title">Warehouse (Leg 1)</h3>
                                </div>
								<div style="float:right; margin:10px">
									<button id="downloadCSV" class="btn btn-warning" style="margin-bottom: 10px;" type="button">Download CSV</button>
									<button id="downloadXLSX" class="btn btn-success" style="margin-bottom: 10px;" type="button">Download XLSX</button>
								</div>
                                <div class="panel-body">
                                 <div class="table-responsive">
                                    <table id="export_table" class="table datatable">
                                        <thead>
                                            <tr>
												<th style="font-size:15px">District</th>
												<th style="font-size:15px">Name of Warehouse</th>
												<th style="font-size:15px">Warehouse ID</th> 
												<th style="font-size:15px">Motorable/Non-Motorable</th>
												<th style="font-size:15px">Warehouse Type</th>
												<th style="font-size:15px">Latitude</th>
												<th style="font-size:15px">Longitude</th>
												<th style="font-size:15px">Storage (Qtl)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
										<?php
										if (!empty($id)) {
											$query = "SELECT * FROM ".$tablename." WHERE 1";
											$result = mysqli_query($con,$query);
											if ($result) {
												while($row = mysqli_fetch_array($result))
												{
													$district = isset($row['district']) ? $row['district'] : '';
													$name = isset($row['name']) ? $row['name'] : '';
													$id_val = isset($row['id']) ? $row['id'] : '';
													$type = isset($row['type']) ? $row['type'] : '';
													$warehousetype = isset($row['warehousetype']) ? $row['warehousetype'] : '';
													$lat = isset($row['latitude']) ? $row['latitude'] : '';
													$lng = isset($row['longitude']) ? $row['longitude'] : '';
													$storage = isset($row['storage']) ? $row['storage'] : (isset($row['capacity']) ? $row['capacity'] : '');
													
													echo "<tr><td>{$district}</td>".
													"<td>{$name}</td>".
													"<td>{$id_val}</td>".
													"<td>{$type}</td>".
													"<td>{$warehousetype}</td>".
													"<td>{$lat}</td>".
													"<td>{$lng}</td>".
													"<td>{$storage}</td></tr>";
												}
											}
										}
										?>
                                        </tbody>
                                    </table>
                                  </div>
                                </div>
                            </div>
                            <!-- END SIMPLE DATATABLE -->

                        </div>
                    </div>

                </div>
                <!-- PAGE CONTENT WRAPPER -->
            </div>
            <!-- END PAGE CONTENT -->
        </div>
        <!-- END PAGE CONTAINER -->



    <!-- START SCRIPTS -->
        <!-- START PLUGINS -->
        <script type="text/javascript" src="js/plugins/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="js/plugins/jquery/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/plugins/bootstrap/bootstrap.min.js"></script>
        <!-- END PLUGINS -->

        <!-- THIS PAGE PLUGINS -->
        <script type='text/javascript' src='js/plugins/icheck/icheck.min.js'></script>
        <script type="text/javascript" src="js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
        <script type="text/javascript" src="js/plugins/datatables/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/tableExport.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jquery.base64.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/html2canvas.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/sprintf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/jspdf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/base64.js"></script>
		
        <script type="text/javascript" src="js/plugins.js"></script>
        <script type="text/javascript" src="js/actions.js"></script>
        <!-- END PAGE PLUGINS -->

		<script>
		function getDateString(){
			var currentDate = new Date();
			var year = currentDate.getFullYear();
			var month = currentDate.getMonth() + 1;
			var day = currentDate.getDate();
			return year + "-" + month + "-" + day;
		}
		
		document.getElementById('downloadCSV').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				const csvResponse = await fetch('api/DownloadOptimalDataWarehouseLeg1.php?format=csv&tableName='+tableName);
				const csvBlob = await csvResponse.blob();
				downloadFile(csvBlob, 'Manipur_Warehouse_Leg1_' + getDateString() + '.csv');
			} catch (error) {
				console.error('Error downloading CSV file:', error);
			}
		});

		document.getElementById('downloadXLSX').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				const excelResponse = await fetch('api/DownloadOptimalDataWarehouseLeg1.php?format=xlsx&tableName='+tableName);
				const excelBlob = await excelResponse.blob();
				downloadFile(excelBlob, 'Manipur_Warehouse_Leg1_' + getDateString() + '.xlsx');
			} catch (error) {
				console.error('Error downloading XLSX file:', error);
			}
		});

		function downloadFile(blob, fileName) {
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = fileName;
			link.click();
			window.URL.revokeObjectURL(url);
		}
		</script>
    </body>
</html>
