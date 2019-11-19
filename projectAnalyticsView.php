<?php
//IMPORT ALL COMPONENTS TO BE USED
include "./components/formGroupCheck.php";

//DB CONNECTION
include "./query/general.php";
include "./query/fetch.php";

$conn = getConn();

//GET INITIAL VALUE OF LOADING SEQUENCE
//$projectId = $_POST["projectID"];

debug_to_console("START DEBUG");
$subjobs = json_decode(getProjectSubjobs("j2202"), true);
echo "<pre>";
print_r($subjobs);
echo "</pre>";

/*
$sql = "SELECT id, firstname, lastname FROM MyGuests";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    }
} else {
    echo "0 results";
}

*/

/*
$query = "SELECT $fields FROM $table $where $sort";
	// die($query);

	$numResults;
	if( isset($_POST['page']) && isset($_POST['perPage']) && !empty($_POST['page']) && !empty($_POST['perPage']) )
	{
		$result = mysqli_query( $conn, $query );
		if( $result === false )
			{ http_response_code(500); die( "SQL Error on line ".__line__."\n".$query."\n".mysqli_error( $conn ) ); return; }
		// Pagination
		$page = $_POST['page'];
		$perPage = $_POST['perPage'];
		$numResults = mysqli_num_rows($result);

		if( $numResults == 0 )
			{ http_response_code(200); die( json_encode([]) ); return; }
		$numPages = ceil( $numResults / $perPage );
		$page = min( $numPages, $page ) - 1;
		$offset = $page * $perPage;
		$query = $query." LIMIT $perPage OFFSET $offset";
	}
	*/
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
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
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

<body onload="main()">
	<div style="width:100%;position: fixed;top: 2rem;z-index: 14; height: calc( 100vh - 2rem ); overflow: auto;background:grey">
		<div class="container-fluid" style="width:80%">
			<div class="title container">
				<h1 class="text-center">Project Name</h1>
			</div>
			<div class="row my-5">
				<div class="col-md-3  ">
					<div class="view-options form-group card card-body">
						<!-- FORM GROUP CHECKS -->
						<?php
						$View = ["Current Budget", "Original Schedule", "Invoiced Amount (Invoice Out)", "Paid amount", "Value", "Amount Spent"];
						$subjobsNames = [];
						$taskNamesWithSubjob = [];
						foreach ($subjobs as $subjob) {
							$subjobsNames[] = $subjob["Name"];
							foreach ($subjob["Tasks"] as $task) {
								$taskNamesWithSubjob[] = $task["Name"] . " (" . $subjob["Name"] . ") ";
							}
						}
						?>
						<?php FormGroupCheck("Subjobs", $subjobsNames) ?>
						<?php FormGroupCheck("Tasks", $taskNamesWithSubjob) ?>
						<?php FormGroupCheck("View", $View) ?>

						<!-- CONTINUE FORMS -->

						<div class="form-group">
							<h5 for="date-group">Date Range</h5>
							<div class="date-group form-inline">
								<input type="date" class="form-control-sm" name="date-start">
								-
								<input type="date" class="form-control-sm" name="date-end">
							</div>

						</div>

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
				<div class="" id="spreadsheet">
				</div>
				<p></p>
			</div>
		</div>
	</div>
</body>
<!-- NOTE COMMENT THIS ON DEPLOYMENT TO WP -->
<link rel="stylesheet" href="./projectAnalytics.css" />
<script src="./CTRgraphing.js"></script>
<script src="./CTRspreadsheet.js"></script>
<script src="./CTRload.js"></script>


<script>
	function main() {
		pageload("j2202");
		mainSpread("j2202");
		mainGraph("j2202");
	}
</script>

</html>