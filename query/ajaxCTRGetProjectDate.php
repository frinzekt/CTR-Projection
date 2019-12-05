<?php
include "./general.php";

/*
    Inputs:
        - $projectId
    Action: Uses SQL to determine project datetimes
    Dependencies:
        -getConn()
    Output: 
        - 
    Return: [$startdate,endDate] (Array of String - Date)
*/
function getProjectDate($projectId, $conn) //REVIEW  - currently using ProjectDateDynamic()
{
    $sql = "SELECT startdate,duedate FROM `jobs` WHERE jobnumber LIKE \"{$projectId}\"";
    $result = mysqli_query($conn, $sql);
    //print($sql);
    $row = mysqli_fetch_row($result) or die(mysqli_connect_error());
    // Row = [startdate,duedate]

    return $row;
}

/*
    Inputs:
        - $projectId
    Action: Uses SQL UNION OF VARIOUS TABLES (mainly with field: jobId) to determine project datetimes
    Dependencies:
        -getConn()
    Output: 
        - 
    Return: [$startdate,endDate] (Array of String - Date)
*/
function getProjectDateDynamic($projectId, $conn)
{
    $sql = "SELECT MIN(date) as StartDate,MAX(date) as EndDate  FROM( \n";


    //ONLY TABLES WITH  "jobID" as a field
    $table = ["invoiceOut", "invoiceOut", "purchaseOrders", "payRoll", "invoiceIn", "invoiceIn"];
    $dateField = ["periodStart", "periodEnd", "date", "date", "periodStart", "periodStart"];

    //EXCEPTIONS 
    $sql .= "SELECT DueDate as Date FROM jobs\n"
        . "WHERE jobNumber=\"$projectId\"\n"
        . "AND DueDate<>'0000-00-00'\n"
        . "UNION\n"
        . "SELECT StartDate as Date FROM jobs\n"
        . "WHERE jobNumber=\"$projectId\"\n"
        . "AND StartDate<>'0000-00-00'\n"
        . "UNION \n";
    //SQL GENERATOR TO UNITE (UNION) ALL DATE FIELDS
    for ($i = 0; $i < count($table); $i++) {
        $sql .= "SELECT {$dateField[$i]} as Date FROM {$table[$i]}\n"
            . "WHERE jobID=\"{$projectId}\"\n"
            . "AND {$dateField[$i]}<>'0000-00-00'\n";

        if ($i == count($table) - 1) { //LAST ITEM
            $sql .= ") \n ";
        } else {
            $sql .= "UNION \n ";
        }
    }
    $sql .= "AS DateTable\n"
        . "WHERE date IS NOT NULL AND date<>\"\"";

    $result = mysqli_query($conn, $sql);
    //  print($sql);
    $row = mysqli_fetch_row($result) or die(mysqli_connect_error());

    // Row = [startdate,duedate]
    return $row;
}


function ajaxCTRGetProjectDate()
{
    // Initial Status Code
    header("HTTP/1.1 501 Process Error");

    $conn = getConn();

    //REQUEST VARIABLES / INPUT
    $projectId = $_REQUEST['projectId'];

    list($startDate, $endDate) = getProjectDateDynamic($projectId, $conn);
    $dateInterval = getDateInterval($startDate, $endDate);

    $outputJson = array(
        "startDate" => $startDate,
        "endDate" => $endDate,
        "dateInterval" => $dateInterval
    );

    header("HTTP/1.1 200 OK");
    $outputJson = json_encode($outputJson);
    die($outputJson);
}
ajaxCTRGetProjectDate();
