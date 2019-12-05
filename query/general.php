<?php //COMMENT THIS FUNCTION WHEN DEPLOYED IN WP

// USED FOR TURNING DAY INTO A STRING SUITABLE FOR STRTOTIME CONVERTION
/*
    Inputs:
        - $day (int)
    Action: Converts the day number into a STR that is convertible using strtotime()
    Return: daytime (string)
*/
function dayToStringConvert($day)
{
    return '+' . $day . 'day';
}

/*
    Inputs:
        - $startDate (string)
        - $endDate (string)
        - $daysInterval (int) - the interval between days (default value = 7 days)
        - $format (string) - custom format of the generated day Interval
    Action:  Generates days that covers all the days between startDate and endDate inclusively with a specfic day interval
    Dependencies:
        - dayToStringConvert <- fetch.php
    Output: NONE
    Return: Returns the days that covers that dates from the start up to the endDate (can overflow) [Array of String]

    eg. $startDate = 2019-05-07
        $endDate = 2019-05-14
        Returns: [2019-05-07,2019-05-14]

    eg. $startDate = 2019-05-07
        $endDate = 2019-05-15
        Returns: [2019-05-07,2019-05-14,2019-05-21]
*/
function getDateInterval($startDate, $endDate, $daysInterval = 7, $format = 'Y-m-d')
{

    $dates = [];

    $current = strtotime($startDate);

    // If the endDate cannot be found... get all the days (daysInterval*10) after startDate
    $last = (($endDate != '0000-00-00') && ($endDate != "")) ? strtotime($endDate)
        : strtotime(dayToStringConvert($daysInterval * 10), $current);
    //$last = strtotime($endDate);

    $timeOffset = dayToStringConvert($daysInterval - 1);
    $daysInterval = dayToStringConvert($daysInterval);

    //Loop until the current day being iterated over is less than the last date
    while ($current <= strtotime($timeOffset, $last)) {

        $dates[] = date($format, $current);
        $current = strtotime($daysInterval, $current);
    }

    return $dates;
}

function getConn()
{
    $servername = "localhost";
    $username = "sustech1_lidia";
    $password = "timebomb1";
    $dbname = "world";


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
