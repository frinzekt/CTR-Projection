<?php
//IMPORT ALL COMPONENTS TO BE USED
include "./components/formGroupCheck.php";

//DB CONNECTION
include "./query/general.php";


$conn = getConn();

//GET INITIAL VALUE OF LOADING SEQUENCE
$projectId = $_REQUEST['projectId'];
$projectName = getProjectName($projectId);
debug_to_console($projectName);
// DATA STRUCTURE INIT
// $project = new viewDataSet($projectId,name)

$subjobsDataSet = [];
$subjobsTasksDataSet = [];



debug_to_console("START DEBUG");
$subjobs = json_decode(getProjectSubjobs($projectId), true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="X-UA-Compatible" content="ie=edge" />
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">



	<!-- Latest compiled JavaScript -->
	<script src="https://code.jquery.com/jquery-3.1.1.min.js">
	</script>
	<!-- NOTE COMMENT THIS ON DEPLOYMENT TO WP -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous">
	</script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous">
	</script>

	<!-- Plotly CDN -->
	<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

	<!-- HandonTable (JS EXCEL) -->
	<script src="https://cdn.jsdelivr.net/npm/handsontable@7.2.2/dist/handsontable.full.min.js"></script>
	<link href="https://cdn.jsdelivr.net/npm/handsontable@7.2.2/dist/handsontable.full.min.css" rel="stylesheet" media="screen">

	<!-- Numjs -->
	<script src="https://cdn.jsdelivr.net/gh/nicolaspanel/numjs@0.15.1/dist/numjs.min.js"></script>

	<title>Project Name: -----</title>
</head>

<body>
	<div style="width:100%;position: fixed;top: 2rem;z-index: 14; height: calc( 100vh - 2rem ); overflow: auto;background:grey">
		<div class="container-fluid" style="width:80%">
			<div class="title container">
				<h1 class="text-center"><?php print($projectName); ?></h1>
			</div>
			<div class="row my-5">
				<div class="col-md-3  ">
					<div class="view-options form-group card card-body">
						<!-- FORM GROUP CHECKS -->
						<?php

						// SETTING DYNAMIC FOR GROUP AND DATA STRUCTURE IDENTIFIERS
						$View = ["Current Budget", "Original Schedule", "Invoiced Amount (Invoice Out)", "Paid amount", "Value", "Amount Spent"];
						$subjobsNames = [];
						$taskNamesWithSubjob = [];

						$subjobsNumbers = [];
						$taskNumbersWithSubjob = [];

						foreach ($subjobs as $subjob) {
							$subjobsNames[] = $subjob["Name"];
							$subjobsNumbers[] = $subjob["Number"];

							// $subjobsDataSet[] = new ViewDataSet($subjob["Number"], $subjob["Name"]);
							// $tasksDataSet = [];
							foreach ($subjob["Tasks"] as $task) {
								$taskNamesWithSubjob[] = $task["Name"] . " (" . $subjob["Name"] . ") ";
								$taskNumbersWithSubjob[] = "{$subjob["Number"]},{$task["Number"]}";

								//CREATING AN ARRAY OF TASKS
								// $tasksDataSet[] = new ViewDataSet($task["Number"], $task["Name"]);
							}
							// $subjobsTasksDataSet[] = $tasksDataSet; //ADDING THE CREATED ARRAY TO ANOTHER ARRAY
						}


						FormGroupCheck("Subjobs", $subjobsNames, $subjobsNumbers);
						FormGroupCheck("Tasks", $taskNamesWithSubjob, $taskNumbersWithSubjob); ?>
						<!-- <?php FormGroupCheck("View", $View) ?> -->

						<!-- CONTINUE FORMS -->

						<!-- <div class="form-group">
							<h5 for="date-group">Date Range</h5>
							<div class="date-group form-inline">
								<input type="date" class="form-control-sm" name="date-start">
								-
								<input type="date" class="form-control-sm" name="date-end">
							</div>

						</div> -->

						<div class="form-group">
							<h5 for="number-mode"> Number Mode </h5>
							<select type="select" class="form-control-sm" placeholder="Number Mode $/%">
								<option value="$">$</option>
								<option value="%">%</option>
								<option value="Both">Both</option>
							</select>
						</div>
						<div>
							<button class="btn btn-primary" onclick="">
								Apply changes
							</button>
							<button id="export-file" class="intext-btn btn btn-primary ">
								Download CSV
							</button>
						</div>
					</div>

				</div>
				<div class="col-md-9 ">
					<div class="row ">
						<div class="container-fluid chart-container card card-body">
							<div class="graph-container" id="graph">
							</div>
						</div>
					</div>

				</div>
			</div>
			<div class="row container-fluid spreadsheet-container card card-body" style="">
				<div class="" id="spreadsheet" style="">
				</div>
				<p></p>
			</div>
		</div>
	</div>
</body>
<!-- NOTE COMMENT THIS ON DEPLOYMENT TO WP -->
<link rel=" stylesheet" href="./projectAnalytics.css" />
<script src="./CTRgraphing.js"></script>
<script src="./CTRspreadsheet.js"></script>
<script src="./CTRload.js"></script>
<script>
	/*
		Inputs:
			- API DATA in CTRgraphload()
		Action: 
			- Calls CTRgraphload() to fetch API DATA (ASYNCHRONOUSLY)
			- Passes the API Data to the spreadsheet and graphing portion
		Dependencies:
			- mainSpread() <- CTRspreadsheet.js
			- mainGraph() <- CTRgraphing.js
			- CTRgraphload() <- CTRload.js
		Output:
			- 
		Return: 
	*/
	async function main() {
		let data = await CTRgraphload();
		await (() => {
			console.log("FETCHING DATA COMPLETE")
			mainSpread(data);
			mainGraph(data);
		})()

	}
	//Ensures codes are only run when everything is loaded
	$(document).ready(() => {
		console.log("DOCUMENT LOADED")
		main();
	})
</script>



</html>