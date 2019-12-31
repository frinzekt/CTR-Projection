<?php
//IMPORT ALL COMPONENTS TO BE USED
include "./components/formGroupCheck.php";
include "./components/modal.php";

//DB CONNECTION
include "./query/general.php";


$conn = getConn();

//GET INITIAL VALUE OF LOADING SEQUENCE
if (isset($_REQUEST['projectId'])) {
	$projectId = $_REQUEST['projectId'];
} else {
	echo "Status Code 200: No Project ID";
	die();
}

//GETTING SOME INFORMATION THAT WILL BE USED TO RENDER THE PAGE
// INFORMATION TAKEN
/*
- Project Name
- Subjobs
- Tasks
*/
$projectName = getProjectName($projectId);
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
	<!-- NOTE COMMENT THIS ON DEPLOYMENT TO WP -->
	<script src="https://code.jquery.com/jquery-3.1.1.min.js">
	</script>

	<!-- BOOTSTRAP -->
	<!-- USED MAINLY FOR CSS LAYOUT -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous">
	</script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous">
	</script>

	<!-- Plotly CDN -->
	<!-- USED FOR DISPLAYING INTERACTIVE GRAPH -->
	<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

	<!-- HandonTable (JS EXCEL) -->
	<!-- USED FOR DISPLAYING SPREADSHEET -->
	<script src="https://cdn.jsdelivr.net/npm/handsontable@7.2.2/dist/handsontable.full.min.js"></script>
	<link href="https://cdn.jsdelivr.net/npm/handsontable@7.2.2/dist/handsontable.full.min.css" rel="stylesheet" media="screen">

	<!-- Numjs -->
	<!-- USED FOR NUMERICAL CALCULATIONS OF HUGE ARRAYS - LIKE NUMPY -->
	<script src="https://cdn.jsdelivr.net/gh/nicolaspanel/numjs@0.15.1/dist/numjs.min.js"></script>

	<title>Project Name: <?php print($projectName); ?></title>
</head>

<body>

	<div class="container-fluid center-block" id="loader-parent">
		<div class="">Loading</div>
		<div class="loader "></div>
	</div>
	<div style="width:100%;position: fixed;top: 2rem;z-index: 14; height: calc( 100vh - 2rem ); overflow: auto;background:grey" id="CTR-content">
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
						$customClass = [];

						foreach ($subjobs as $subjob) {
							$subjobsNames[] = $subjob["Name"];
							$subjobsNumbers[] = $subjob["Number"];

							foreach ($subjob["Tasks"] as $task) {
								$taskNamesWithSubjob[] = $task["Name"] . " (" . $subjob["Name"] . ") ";
								$taskNumbersWithSubjob[] = "{$subjob["Number"]},{$task["Number"]}";
								$customClass[] = "subjob-{$subjob["Number"]}";
							}
						}

						//OTHERS CATEGORY TO BE ADDED IN THE LIST
						// See eventHandler from CTReventHandler.js for the use
						// Summary, used to indicate selectors that are fed to a component based PHP - server-side rendered
						$subjobsNames[] = "Other Subjobs";
						$subjobsNumbers[] = "-1";

						$taskNamesWithSubjob[] = "Other Tasks";
						$taskNumbersWithSubjob[] = "-1,-1";

						//  "subjob--0" instead of "subjob--1" in order for other tasks to show even if Other Subjobs is unchecked
						$customClass[] = "subjob--0";

						FormGroupCheck("Subjobs", $subjobsNames, $subjobsNumbers, "handleSubjobChange");
						FormGroupCheck("Tasks", $taskNamesWithSubjob, $taskNumbersWithSubjob, "handleTaskChange", $customClass); ?>

						<!-- NUMBER MODE -->
						<div class="form-group">
							<h5 for="number-mode"> Number Mode </h5>
							<select type="select" class="form-control-sm" placeholder="Number Mode $/%" id="numberMode">
								<option value="$">$</option>
								<option value="%">%</option>
								<option value="Both">Both</option>
							</select>
						</div>
						<!-- DAYS INTERVAL FORM -->
						<div class="form-group">
							<h5 for="numberMode"> Days Interval As</h5>
							<select type="select" class="form-control-sm" placeholder="Show Date Interval As" id="daysInterval">
								<option value="10">10 days</option>
								<option value="30">30 days</option>
								<option value="60">60 days</option>
							</select>
						</div>

						<!-- APPLY CHANGES BUTTON -->
						<div>
							<button class="btn btn-primary" onclick="handleChange(this)">
								Apply changes
							</button>
							<!-- HANDSONTABLE DOWNLOAD CSV -->
							<button id="export-file" class="intext-btn btn btn-primary ">
								Download CSV
							</button>
						</div>
					</div>

				</div>

				<!-- PLOTLY GRAPH CONTAINER -->
				<div class="col-md-9 ">
					<div class="row ">
						<div class="container-fluid chart-container card card-body">
							<div class="graph-container container-fluid" id="graph">
							</div>
						</div>
					</div>

				</div>
			</div>

			<!-- HANDSONTABLE SPREADSHEET CONTAINER -->
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

<script src="./CTReventHandler.js"></script>
<script src="./CTRcalculation.js"></script>
<script src="./CTRdataParser.js"></script>
<script>
	//DECLARED AS GLOBAL VARIABLE FOR EVENT HANDLERS TO GET VALUE
	let data;
	let graphData;
	let spreadsheetData = [];
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
		hideContent();
		let dateData = await getProjectDate();
		let dateInterval = dateData.dateInterval;
		let remaining = dateInterval.length;
		let startIndex = 0;
		let endIndex = 0;

		let LIMIT = 10; //TAKES DATA OF 10 WEEKS AT A TIME
		let noLoops = Math.ceil(remaining / LIMIT)

		//LOOPING TO GET NUMBER OF ITEMS (LIMIT), BUT LAST ITERATION ONLY GETS THE REMAINING
		for (let i = 0; i < noLoops; i++) {
			try {

				if (i == 0) {
					// First Iteration
					endIndex = startIndex + LIMIT - 1
					if (i == noLoops - 1) {
						data = await CTRgraphload(dateInterval[startIndex], dateInterval[endIndex], remaining, true);
					} else {
						data = await CTRgraphload(dateInterval[startIndex], dateInterval[endIndex], remaining);
					}

					data.dateInterval = dateInterval

					startIndex += LIMIT
				} else if (i == noLoops - 1) {
					//LAST ITERATION OF LOOP
					endIndex = remaining - 1;
					fetchData = await CTRgraphload(dateInterval[startIndex], dateInterval[endIndex], remaining, true);
					mergeAPIArray(data, fetchData)
					console.log(data)

				} else {
					endIndex = startIndex + LIMIT - 1
					fetchData = await CTRgraphload(dateInterval[startIndex], dateInterval[endIndex], remaining);
					mergeAPIArray(data, fetchData)

					startIndex += LIMIT
				}
			} catch (error) {
				console.log(error + " in API");
				showContent();
			}
		}


		//Creates a promise that says "rendering will eventually finish"
		let promise = new Promise((resolve, reject) => {
			console.log("FETCHING DATA COMPLETE")
			mainGraph(data)
			resolve(mainSpread(data));
		})

		//Calls the promise and awaits for the result
		let result = await promise;

		//When rendering is finished on the promise, show content
		showContent();

	}
	//Ensures codes are only run when everything is loaded
	jQuery(document).ready(() => {
		main();
	})
</script>



</html>