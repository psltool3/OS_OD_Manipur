<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

?>

 <style>
        /* Increase the font size for the entire page */
        body {
            font-size: 16px; /* Change this value to increase or decrease the base font size */
        }

        /* Increase the font size for specific elements */
        .breadcrumb,
        .panel-title,
        .btn {
            font-size: 12px; /* Adjust the font size for breadcrumbs, panel titles, and buttons */
        }

        /* Increase font size for tables */
        table,
        th,
        td {
            font-size: 15px; /* Font size for table elements */
        }

        /* Increase the font size for form labels, inputs, and buttons */
        label,
        input,
        button {
            font-size: 12px; /* Font size for form elements and buttons */
        }

        /* Increase the font size for specific elements within the page */
        .popup,
        .help-block {
            font-size: 16px; /* Font size for popup elements and help blocks */
        }
    </style>
              
                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="#">Home</a></li>                    
                    <li class="active">All Optimised Leg1 Data</li>
                </ul>
                <!-- END BREADCRUMB -->                       
                
				
				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">                
                
                    <div class="row">
                        <div class="col-md-12">


                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
								<div class="panel-heading">                                
                                    <h3 class="panel-title">Data</h3> 
                                </div>
								<div class="panel-body">
                                 <div class="table-responsive">
                                    <table id="export_table" class="table">
                                        <thead>
                                            <tr>
												<th style="font-size:16px">Year</th>
                                                <th style="font-size:16px">Month</th>
                                                <th style="font-size:16px">Applicable Month</th>
                                                <th style="font-size:16px">Warehouse</th>
                                                <th style="font-size:16px">FCI</th>
                                                <th style="font-size:16px">Optimised Data</th>
												<th style="font-size:16px">Generate Data</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table_body">
										<?php
										
										$query = "SELECT * FROM optimised_table_leg1 WHERE 1";
										$result = mysqli_query($con,$query);
										$numrows = mysqli_num_rows($result);
										while($row = mysqli_fetch_array($result))
										{
											$temp_id = (string)$row['id'];
											echo "<tr><td>{$row['year']}</td>".
											 "<td>{$row['month']}</td>".
											 "<td>{$row['applicable']}</td>".
											 "<td> <button class='btn btn-info btn-rounded' onclick=\"warehouse_open('{$temp_id}')\">View Warehouses</button></td>".
              								 "<td> <button class='btn btn-warning btn-rounded' onclick=\"fps_open('{$temp_id}')\">View FCI</button></td>".
              								 "<td> <button class='btn btn-danger btn-rounded' onclick=\"optimised_open('{$temp_id}')\">View Data</button></td>".
											 "<td> <button class='btn btn-success btn-rounded' onclick=\"generate_report('{$temp_id}')\">View Report</button></td></tr>";
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
		var methodCalled = "";
		var uidCalled = "";
		function post(params,file) {
			
			method = "post"; 
			path = file;
			
			var form = document.createElement("form");
			form.setAttribute("method", method);
			form.setAttribute("action", path);

			for(var key in params) {
				if(params.hasOwnProperty(key)) {
					var hiddenField = document.createElement("input");
					hiddenField.setAttribute("type", "hidden");
					hiddenField.setAttribute("name", key);
					hiddenField.setAttribute("value", params[key]);

					form.appendChild(hiddenField);
				 }
			}

			document.body.appendChild(form);
			form.submit();
		}

		function warehouse_open(temp_id){
			post({id:temp_id,step:"leg1"} ,"WarehouseView.php");
		}
		
		function fps_open(temp_id){
			post({id:temp_id,step:"leg1"} ,"FPSView.php");
		}
		
		function optimised_open(temp_id){
			post({id:temp_id,step:"leg1"} ,"OptimisedDataView.php");
		}
		
		function generate_report(temp_id){
			post({id:temp_id,step:"leg1"} ,"GenerateDataView.php");
		}
		
		</script>	
    </body>
</html>
