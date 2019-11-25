<?php //COMMENT THIS FUNCTION WHEN DEPLOYED IN WP
function getConn()
{
    $servername = "localhost";
    $username = "sustech1_lidia";
    $password = "timebomb1";
    $dbname = "sustech1_hourglass";


    // Create connection
    $conn = mysqli_connect($servername, $username, $password);
    $db = mysqli_select_db($conn, $dbname);

    return $conn;
}
function getProjectName($projectId)
{
    $conn = getConn();
    $dbname = "sustech1_hourglass";
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $db = mysqli_select_db($conn, $dbname); //activating the hourglass database
    $sql = "SELECT name FROM jobs WHERE jobnumber=\"{$projectId}\"";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($result);
    return $row[0];
}
//GET PROJECT SUBJOBS ALREADY EXIST
//-> RETURNS the jobs and task
function getProjectSubjobs($jobId)
{
    $conn = getConn();
    $dbname = "sustech1_hourglass";
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $db = mysqli_select_db($conn, $dbname); //activating the hourglass database

    //$jobId = $_POST['jobId'];
    $subjobs = [];

    $query = "SELECT Number, Name FROM subjobs WHERE jobID='$jobId' ORDER BY length(Number) ASC, Number ASC";
    $result = mysqli_query($conn, $query);
    if ($result === false) {
        http_response_code(500);
        die($query . "\nError description: " . mysqli_error($conn));
    }
    // if( mysqli_num_rows( $result ) == 0 ) { die( json_encode([]) ); return; }

    while ($subjob = mysqli_fetch_row($result)) {
        $q = "SELECT Number, Name FROM tasks WHERE Job='$jobId' AND Subjob='$subjob[0]' ORDER BY length(Number) ASC, Number ASC";
        $r = mysqli_query($conn, $q);
        if ($result === false) {
            http_response_code(500);
            die($query . "\nError description: " . mysqli_error($conn));
        }
        if (mysqli_num_rows($r) == 0) {
            die(json_encode([]));
            return;
        }

        $tasks = [];
        while ($task = mysqli_fetch_row($r)) {
            $tasks[] = array("Number" => $task[0], "Name" => $task[1]);
        }
        $subjobs[] = array("Number" => $subjob[0], "Name" => $subjob[1], "Tasks" => $tasks);
    }

    http_response_code(200); //IDEA: CREATE NEW FUNCTION THAT IS AN AJAX VERSION THAT EXTRACTS POST VALUES AND THEN CALLS THIS FUNCTION
    //die(json_encode($subjobs));
    return json_encode($subjobs);
}


function debug_to_console($data)
{
    echo "<script>console.log('PHP Debug Objects: ";
    $output = $data;
    if (is_array($output)) {
        echo json_encode($output);
    } else {

        echo $output;
    }
    echo "' );</script>";
}

class viewDataSet
{
    public $number;
    public $name;

    public $date = []; //CHECK NOTES ON THIS ONE
    public $currentBudget = [];
    public $originalSchedule = [];
    public $invoicedAmount = [];
    public $paidAmount = [];
    public $value = [];
    public $amountSpent = [];

    //SETUP PRESETS
    function __construct($number, $name)
    {
        $this->number = $number;
        $this->name = $name;
    }
}
