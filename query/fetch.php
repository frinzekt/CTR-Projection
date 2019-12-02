<?php
/*
    NOTE: ALL FUNCTION CREATED HERE ARE HELPER FUNCTIONS FOR THE MOST BOTTOM FUNCTION
*/
if (is_admin()) {
    /* Graph magic ctr functiosnt */
    add_action('wp_ajax_ajaxCTRload', 'ajaxCTRload');
    add_action('wp_ajax_nopriv_ajaxCTRload', 'ajaxCTRload');
}

include "./general.php";

//CALCULATES THE VALUE NORMALIZE TO 0 TO 1 MULTIPLIED BY THE MAX
/*
    Inputs:
        - $t  (number) - x-axis -> input usually between -1 to 1
        - $max (float) - maximum value of the logistic function
    Action: Utilizes logistic function to normalize values
    Output: NONE
    Return: Value between 0 to max (float)
*/
function sigmoid($time, $max)
{
    //SHIFT CHANGE IS A COEFFICIENT ADJUSTER
    $shiftChange = $max / 2 / 5000;
    return $max / (1 + exp(-$shiftChange * $time));
}

/*
    Inputs:
        - start (number)- start of the array to be created
        - end (number) - end of the array to be created
        - $n (int)- number of terms
    Action: Creates an Array of $n terms equally spaced between start and end
    Output: NONE
    Return: Array of $n terms equally spaced (Array float)
*/
function linspace($start, $end, $n)
{
    $step = ($end - $start) / ($n - 1);
    return range($start, $end, $step);
}

//GENERATES ARRAY OF ORIGINAL SCHEDULE BASED ON THE MAX BUDGET AND THE DATES IN BETWEEN
/*
    Inputs:
        - maxBudget (float) - sets the maximum point of the schedule
        - startDate (string)
        - endDate (string)
    Action: Uses sigmoid function to create normalize values between 0 to the maxBudget with the same number of days as the interval used
    Dependencies:
    - linspace <- fetch.php
    - getDateInterval <- fetch.php
    - sigmoid <- fetch.php
    Output: NONE
    Return: Returns an array filled with y-values of the logistic function (Array)
*/
function generateOriginalSchedule($maxBudget, $startDate, $endDate)
{
    $dates = getDateInterval($startDate, $endDate, 7);
    $dataSize = sizeof($dates);

    $sigmoidDataSet = linspace(-1, 1, $dataSize);

    $originalSchedule = [];

    foreach ($sigmoidDataSet as $dataSet) {
        $originalSchedule[] = sigmoid($dataSet, $maxBudget);
    }

    return $originalSchedule;
}

/*
    Inputs:
        - $sql
    Action: Runs the SQL and fetches the first row first column values
    Dependencies:
        - getConn
    Output: NONE
    Return: Returns the single value of the Aggregated SQL (Array)
*/
function fetchAggregateValue($sql)
{
    $conn = getConn();
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

/*
    Inputs:
        - $sql
    Action: Runs the SQL and fetches the whole table
    Dependencies:
        - getConn
    Output: none
    Return: returns the whole table generated from the sql fetch (Array)
*/
function fetchArrayValues($sql)
{
    $conn = getConn();

    $result = mysqli_query($conn, $sql);
    if (!($result = mysqli_query($conn, $sql))) {
        echo ("Error description: " . mysqli_error($conn));
        die("\n {$sql}");
    }
    $table = mysqli_fetch_all($result);


    return $table;
}

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
function getProjectDate($projectId) //REVIEW  - currently using ProjectDateDynamic()
{
    $sql = "SELECT startdate,duedate FROM `jobs` WHERE jobnumber LIKE \"{$projectId}\"";

    $conn = getConn();
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
function getProjectDateDynamic($projectId)
{
    $sql = "SELECT MIN(date) as StartDate,MAX(date) as EndDate  FROM( \n";
    $conn = getConn();

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
/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date) 
    Action: Gets the Current Budget by aggregating the "values" field in purchaseOrders Table in between date intervals 
    Output: NONE
    Return: (Array) Structure
    | 6/05/2019 | 15/10/2019 |
    |-----------|------------|
    | 100       | 300        |
    
*/
function getCurrentBudgetJob($projectId, $startDate, $endDate)
{
    $sql = "SELECT \n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM(CASE WHEN date<=\"{$date}\" THEN value ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `purchaseOrders` WHERE jobId=\"{$projectId}\"";

    //print($sql);
    return fetchArrayValues($sql);
}


/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets the Purchase Order Values from the purchase Order Table (Field: Value)
    Output:
        - NONE
    Return: (Array) Structure

    | purchaseOrder | 6/05/2019 | 15/10/2019 |
    |---------------|-----------|------------|
    | 1             | 100       | 300        |
    | 2             | 50        | 200        |

*/
function getPODetails($projectId, $startDate, $endDate)
{
    $sql = "SELECT purchaseOrder, \n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM(CASE WHEN date<=\"{$date}\" THEN value ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `purchaseOrders` WHERE jobId=\"{$projectId}\"\n"
        . " GROUP BY purchaseOrder";

    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: Generates SQL that gets the invoiced amount by using invoiceOut Table (to filter amount by dates) and invoiceUnpaid (to get the number*price)
    Dependencies:
        - getDateInterval() <- fetch.php
    Output:
        - NONE
    Return: $sql
*/
function getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, $groupBy)
{
    //GET ALL INVOICE IDs WITHIN THE DATE
    $sql = "SELECT $groupBy,\n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM( CASE WHEN invoiceOut.periodEnd<=\"{$date}\" THEN \n"
            . "invoiceUnpaid.number*invoiceUnpaid.price ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoiceOut` \n"
        . "INNER JOIN invoiceUnpaid On invoiceOut.invoiceID = invoiceUnpaid.invoiceID \n"
        . "WHERE invoiceUnpaid.jobID=\"{$projectId}\"\n"
        . "GROUP BY $groupBy";
    return $sql;
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced amount group by ID
    Dependencies:
        - getInvoicedAmountSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
    | 3911015   | 0          | 20         |
*/
function getInvoicedAmount($projectId, $startDate, $endDate)
{
    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceOut.invoiceID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced amount group by Job
    Dependencies:
        - getInvoicedAmountSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    | jobID     | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/

function getInvoicedAmountGroupByJob($projectId, $startDate, $endDate)
{

    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced amount group by subjob
    Dependencies:
        - getInvoicedAmountSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    | subjobID  | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/

function getInvoicedAmountGroupBySubjob($projectId, $startDate, $endDate)
{

    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.subjob");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced amount group by subjob,task
    Dependencies:
        - getInvoicedAmountSQLTemplate()
    Output:
        - 
    Return:  (Array)
    | subjobID  | taskID    | 30/09/2019 | 31/10/2019 |
    |-----------|-----------|------------|------------|
    | 3911012   | 3911012   | 1760       | 1760       |
    | 3911013   | 3911013   | 100        | 100        |
*/

function getInvoicedAmountGroupByTask($projectId, $startDate, $endDate)
{

    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.subjob,invoiceUnpaid.taskId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: Generates SQL that gets the reconciled amount by using invoiceOut Table (to filter amount by dates) and invoiceUnpaid (to get the number*price)
    Dependencies:
        - getDateInterval() <- fetch.php
    Output:
        - NONE
    Return: $sql
*/
function getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, $groupBy)
{
    //GET ALL INVOICE IDs WITHIN THE DATE
    $sql = "SELECT $groupBy,\n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM( CASE WHEN invoiceOut.periodEnd<=\"{$date}\" THEN \n"
            . "invoiceUnpaid.reconciled ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoiceOut` \n"
        . "INNER JOIN invoiceUnpaid On invoiceOut.invoiceID = invoiceUnpaid.invoiceID \n"
        . "WHERE invoiceUnpaid.jobID=\"{$projectId}\"\n"
        . "GROUP BY $groupBy";
    return $sql;
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets reconciled amount group by ID
    Dependencies:
        - getReconciled AmountSQLTemplate() <- fetch.php
    Output:
        - NONE
    Return:  (Array)
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/
function getReconciledAmountGroupById($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.invoiceID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets reconciled amount group by Job
    Dependencies:
        - getReconciledAmountSQLTemplate() <- fetch.php
    Output:
        - 
    Return:  (Array)
    | jobID     | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/
function getReconciledAmountGroupByJob($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets reconciled amount group by subjob
    Dependencies:
        - getReconciledAmountSQLTemplate() <- fetch.php
    Output:
        - NONE
    Return:  (Array)
    | subjob    | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/

function getReconciledAmountGroupBySubjob($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.subjob");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets reconciled amount group by task
    Dependencies:
        - getReconciledAmountSQLTemplate() <- fetch.php
    Output:
        - 
    Return:  (Array)
    | subjob    | taskId    | 30/09/2019 | 31/10/2019 |
    |-----------|-----------|------------|------------|
    | 3911012   | 3911012   | 1760       | 1760       |
    | 3911013   | 3911013   | 100        | 100        |
*/
function getReconciledAmountGroupByTask($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.subjob,invoiceUnpaid.taskId");
    //print($sql);
    return fetchArrayValues($sql);
}

/* Amount Spent:
-> Employee
-> Contractor
-> Expenses
*/

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: Generates SQL that gets the payroll amount by using payroll Table (units*price)
    Dependencies:
        - getDateInterval() <- fetch.php
    Output:
        - 
    Return: $sql
*/
function getPayrollSQLTemplate($projectId, $startDate, $endDate, $groupBy)
{
    //CHANGEABLE GROUP BY
    $sql = "SELECT {$groupBy},\n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM(CASE WHEN date<=\"{$date}\" THEN units*price ELSE 0 END) AS \"{$date}\"";
        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }
    $sql .= "FROM `payRoll` WHERE jobID=\"{$projectId}\"\n"
        . "GROUP BY {$groupBy}";

    return $sql;
}
/*
    Inputs:
        - $projectID (string)
        - $startDate (string - Date)
        - $enddate (string - Date)
    Action: Gets the payroll amount GROUP BY EMPLOYEE ID
    Dependencies:
        - getPayrollSQLTemplate() <- fetch.php
    Output:
        - 
    Return: 

    | employeeID       | 27/08/2019 | 30/09/2019 |
    |------------------|------------|------------|
    | a@sustech.net.au | 2223.6     | 5559       |
    | b@sustech.net.au | 2223.6     | 5329       |
*/
function getPayrollGroupByEId($projectId, $startDate, $endDate)
{
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "employeeID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectID (string)
        - $startDate (string - Date)
        - $enddate (string - Date)
    Action: Gets the payroll amount GROUP BY JOBID
    Dependencies:
        - getPayrollSQLTemplate() <- fetch.php
    Output:
        - 
    Return: 

    | jobID          | 27/08/2019 | 30/09/2019 |
    |----------------|------------|------------|
    | 1              | 2223.6     | 5559       |
    | 2              | 2223.6     | 5329       |
*/
function getPayrollGroupByJob($projectId, $startDate, $endDate)
{
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectID (string)
        - $startDate (string - Date)
        - $enddate (string - Date)
    Action: Gets the payroll amount GROUP BY SUBJOBID
    Dependencies:
        - getPayrollSQLTemplate() <- fetch.php
    Output:
        - 
    Return: 

    | subjobID       | 27/08/2019 | 30/09/2019 |
    |----------------|------------|------------|
    | 1              | 2223.6     | 5559       |
    | 2              | 2223.6     | 5329       |
*/

function getPayrollGroupBySubjob($projectId, $startDate, $endDate)
{
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "subjob");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectID (string)
        - $startDate (string - Date)
        - $enddate (string - Date)
    Action: Gets the payroll amount GROUP BY SUBJOB TASK
    Dependencies:
        - getPayrollSQLTemplate() <- fetch.php
    Output:
        - 
    Return: 

    | subjob         | task           | 27/08/2019 | 30/09/2019 |
    |----------------|----------------|------------|------------|
    | 1              | 1              | 2223.6     | 5559       |
    | 2              | 2              | 2223.6     | 5329       |
*/

function getPayrollGroupByTask($projectId, $startDate, $endDate)
{
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "subjob,task");
    //print($sql);
    return fetchArrayValues($sql);
}

// CONTRACTOR / INVOICE IN
/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: Generates SQL that gets the invoiced In amount by using invoiceIn Table (to filter amount by dates) and invoiceUnpaid (to get the number*price)
    Dependencies:
        - getDateInterval() <- fetch.php
    Output:
        - 
    Return: $sql
*/
function getInvoicedInSQLTemplate($projectId, $startDate, $endDate, $groupBy)
{
    $sql = "SELECT {$groupBy},\n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM( CASE WHEN invoiceIn.periodEnd<=\"{$date}\" THEN \n"
            . "invoiceUnpaid.number*invoiceUnpaid.price ELSE 0 END) AS \"{$date}\"";
        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoiceIn`\n"
        . "INNER JOIN invoiceUnpaid ON\n"
        . "invoiceIn.invoiceInId=invoiceUnpaid.invoiceID\n"
        . "WHERE invoiceUnpaid.jobID=\"{$projectId}\"\n"
        . "GROUP BY {$groupBy}";
    return $sql;
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced IN amount group by invoice ID
    Dependencies:
        - getInvoicedINAmountSQLTemplate()
    Output:
        - 
    Return:  (Array)
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
    | 3911015   | 0          | 20         |
*/

function getInvoicedInGroupById($projectId, $startDate, $endDate)
{
    $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceIn.invoiceInId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets invoiced IN amount group by job ID
    Dependencies:
        - getInvoicedINAmountSQLTemplate()
    Output:
        - 
    Return:  (Array)
    | jobID     | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
    | 3911015   | 0          | 20         |
*/
function getInvoicedInGroupByJob($projectId, $startDate, $endDate)
{
    /*Structure
        | invoiceInId | contractorId | 30/09/2019 | 31/10/2019 |
        |-------------|--------------|------------|------------|
        | i5          | 39           | 60         | 60         |
        | i6          | 39           | 60         | 60         |
    */
    $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

//  REVIEW 
// function getInvoicedInGroupBySubJob($projectId, $startDate, $endDate)
// {
//     /*Structure
//         | invoiceInId | contractorId | 30/09/2019 | 31/10/2019 |
//         |-------------|--------------|------------|------------|
//         | i5          | 39           | 60         | 60         |
//         | i6          | 39           | 60         | 60         |
//     */
//     $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceUnpaid.jobID");
//     return ($sql);
// }

//Expenses
/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: Generates SQL that gets the expenses group by $groupby in between dates $startDate 0 $endDate
    Dependencies:
        - getDateInterval() <- fetch.php
    Output:
        - 
    Return: $sql
*/
function getExpensesSQLTemplate($projectId, $startDate, $endDate, $groupBy)
{
    $sql = "SELECT {$groupBy},\n";

    $dates = getDateInterval($startDate, $endDate, 7);

    $numItems = count($dates);
    $i = 0;
    foreach ($dates as $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM(CASE WHEN dateOfExpense<=\"{$date}\" THEN netCost ELSE 0 END) AS \"{$date}\"";
        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `expenses` WHERE jobid=\"{$projectId}\"\n"
        . "GROUP BY {$groupBy}";

    return $sql;
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets expenses group by expenseID, subjobID
    Dependencies:
        - getExpensesSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    | expenseId | subjobId | 21/08/2019 | 20/08/2019 |
    |-----------|----------|------------|------------|
    | 2         |          | 1040       | 0          |
*/
function getExpensesGroupById($projectId, $startDate, $endDate)
{
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "expenseId,subjobId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets expenses group by jobId
    Dependencies:
        - getExpensesSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    |    jobId | 21/08/2019 | 20/08/2019 |
    |----------|------------|------------|
    |     114  | 1040       | 0          |
*/
function getExpensesGroupByJob($projectId, $startDate, $endDate)
{
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "jobId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets expenses group by subjob
    Dependencies:
        - getExpensesSQLTemplate()
    Output:
        - NONE
    Return:  (Array)
    | subjobId | 21/08/2019 | 20/08/2019 |
    |----------|------------|------------|
    |     114  | 1040       | 0          |
*/

function getExpensesGroupBySubjob($projectId, $startDate, $endDate)
{
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "subjobId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets expenses group by subjobId, taskId
    Dependencies:
        - getExpensesSQLTemplate()
    Output:
        - None
    Return:  (Array)
    | subjobId | task     | 21/08/2019 | 20/08/2019 |
    |----------|----------|------------|------------|
    |     114  |     114  | 1040       | 0          |
*/

function getExpensesGroupByTask($projectId, $startDate, $endDate)
{
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "subjobId,taskId");
    //print($sql);
    return fetchArrayValues($sql);
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $select (string)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template

    Action: Get the value of project at certain point of time by a specific grouping
        - This is done with TASK
        - INNER JOIN TASKASSIGNED
        - INNER JOIN DELIVERABLES
        - LEFT JOIN (DELIVERABLE HISTORY INNER JOIN (MAXDATE GROUP BY DELIVERABLEID AND EMPLOYEEID))
    Dependencies:
        - getDateInterval()
    Output:
        - None
    Return: Value SQL
*/
function getValueSQLTemplate($projectId, $dates, $select, $groupBy)
{
    $sql = "";
    if ($select != "") {
        $sql .= "SELECT {$select},\n";
    } else {
        $sql .= "SELECT \n";
    }

    $numItems = count($dates);
    //LOOP TO CREATE REPEATING SUMS
    foreach ($dates as $i => $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        $sql .= "SUM(T.quantity*TA.billableRate*TA.hours*d{$i}.progress/100) AS \"{$date}\"";
        if ($i === $numItems - 1) { // LAST INDEX CHECKER
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }
    // INNER JOIN WITH TASK ASSIGNED AND DELIVERABLES
    $sql .= "FROM tasks T\n"
        . "INNER JOIN tasksAssigned AS TA on T.Number=TA.taskId AND T.Job = TA.jobID AND T.Subjob = TA.subjobID\n"
        . "INNER JOIN deliverables AS Dd on T.Number=Dd.taskId AND T.Job = Dd.jobId AND T.Subjob = Dd.subjobId\n";

    //LOOP TO CREATE REPEATING INNER JOINS OF ALL THE DELIVERABLE HISTORY THAT ARE APPROVED WITH THEIR RECENT DATE PROGRESSION
    foreach ($dates as $i => $date) { //CREATE ALL THE COLUMNS TO BE FETCH ON DAYS
        // LEFT JOIN WITH A SUBQUERY
        // SUBQUERY: DELIVERABLE HISTORY INNER JOIN DELIVERABLE HISTORY (WITH MAX DATE)
        $sql .= "LEFT JOIN(SELECT d.deliverableId,d.employeeID,d.progress FROM deliverableHistory d \n\n"
            . "INNER JOIN (SELECT deliverableId,employeeId,max(date) as maxDate FROM deliverableHistory WHERE date<=\"{$date}\" AND status \n"
            . "LIKE \"approved\" GROUP BY deliverableId,employeeId) dfilter \n"
            . "        on d.deliverableId = dfilter.deliverableId  \n"
            . "AND d.employeeId = dfilter.employeeId \n"
            . "AND d.date = dfilter.maxDate \n"
            . ") AS d{$i} on TA.employeeID = d{$i}.employeeID AND Dd.deliverableId = d{$i}.deliverableId\n\n";
    }

    $sql .= "WHERE T.Job=\"{$projectId}\"\n"
        . "GROUP BY {$groupBy}";
    return $sql;
}

function array_append($array1, $array2)
{
    if ($array1 == 0) { //FIRST RUN
        $array1 = array_merge($array2);
    } else {
        $array1 = array_merge($array1, $array2);
    }

    return $array1;
}
/*
    Inputs:
          - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
        - $select (string)
        - $groupBy (string) - specifies the grouping aggregation and the selection of the SQL Template
    Action: DUE TO THE LIMITATION OF MULTIPLE JOIN BY SQL, THIS FUNCTION IS CREATED TO MERGE SIMILAR QUERIES
    Dependencies:
        - 
    Output:
        - 
    Return: array of specified structure in groupby
*/
function getValueGroupMultiJoin($projectId, $startDate, $endDate, $groupBy)
{
    $LIMIT = 55; //SQL JOINS LIMIT AT 60
    $dates = getDateInterval($startDate, $endDate);
    $groupBy = $select = $groupBy;
    $result = 0;
    $remaining = count($dates);
    $sliceStartIndex = 0;

    $noLoops = ceil(count($dates) / $LIMIT);

    for ($i = 0; $i < $noLoops; $i++) {

        //LAST ITERATION OF LOOP
        if ($i == $noLoops - 1) {
            $length = $remaining - $sliceStartIndex;
            $sql = getValueSQLTemplate($projectId, array_slice($dates, $sliceStartIndex, $length), $select, $groupBy);
            $result = array_append($result, fetchArrayValues($sql));
        } else {

            $sql = getValueSQLTemplate($projectId, array_slice($dates, $sliceStartIndex, $LIMIT), $select, $groupBy);
            $sliceStartIndex = $LIMIT;
            $result = array_append($result, fetchArrayValues($sql));
        }
        $select = "";
    }

    // print_r($result);
    return $result;
}
/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets value of project group by Job
    Dependencies:
        - getValueSQLTemplate()
    Output:
        - None
    Return:  (Array)
    |    jobId | 21/08/2019 | 20/08/2019 |
    |----------|------------|------------|
    |     114  | 1040       | 0          |
*/

function getValueGroupByJob($projectId, $startDate, $endDate)
{
    return getValueGroupMultiJoin($projectId, $startDate, $endDate, "T.Job");
}

/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets value of project group by sub
    Dependencies:
        - getValueSQLTemplate()
    Output:
        - None
    Return:  (Array)
    |   subjob | 21/08/2019 | 20/08/2019 |
    |----------|------------|------------|
    |     114  | 1040       | 0          |
*/
function getValueGroupBySubjob($projectId, $startDate, $endDate)
{
    return getValueGroupMultiJoin($projectId, $startDate, $endDate, "T.Subjob");
}


/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets value of project group by sub
    Dependencies:
        - getValueSQLTemplate()
    Output:
        - 
    Return:  (Array)
    |   subjob |   task   | 21/08/2019 | 20/08/2019 |
    |----------|----------|------------|------------|
    |     114  |     114  | 1040       | 0          |
*/
function getValueGroupByTask($projectId, $startDate, $endDate)
{
    return getValueGroupMultiJoin($projectId, $startDate, $endDate, "T.Subjob,T.Number");
}

/*
    Inputs:
        - $job array (2D from SQL)
    Action: Since all the queries with job rows are only 1D Array yet SQL gives 2D Array, API packaging is better with 1D array for the purpose of easy extraction in frontend
    Dependencies:
        - 
    Output:
        - 
    Return: $job row (1D array), if the $job empty, returns an empty array
*/
function extractJobRow($job)
{
    if (!empty($job)) {
        return $job[0];
    } else {
        return $job;
    }
}

/*
    Inputs:
        - $array
    Action: Converts direct null arrays to empty array to avoid index error in frontend
    Dependencies:
        - NONE
    Output:
        - NONE
    Return: empty array or array itself
*/
function nullToEmpty($array)
{
    if ($array == null) {
        $array = [];
    }
    return $array;
}

/*
    Inputs:
        - $projectId (from POST OR GET REQUEST)
    Action: Packages an API from all the aggregated data from fetch.php
    Dependencies:
        - getProjectDate() <- fetch.php
        - getDateInterval() <- fetch.php
        - extractJobRow() <- fetch.php
        - getCurrentBudgetJob() <- fetch.php
        - generateOriginalSchedule() <- fetch.php
        - getPODetails() <- fetch.php
        - getInvoicedAmount() <- fetch.php
        - getInvoicedAmountGroupByJob() <- fetch.php
        - getReconciledAmountGroupById() <- fetch.php
        - getReconciledAmountGroupByJob() <- fetch.php
        - getPayrollGroupByEId() <- fetch.php
        - getPayrollGroupByJob() <- fetch.php
        - getPayrollGroupBySubjob() <- fetch.php
        - getPayrollGroupByTask() <- fetch.php
        - getInvoicedInGroupById() <- fetch.php
        - getInvoicedInGroupByJob() <- fetch.php
        - getExpensesGroupById() <- fetch.php
        - getExpensesGroupByJob() <- fetch.php
        - getExpensesGroupBySubjob() <- fetch.php
        - getExpensesGroupByTask() <- fetch.php
    Output:
        - API package with structure: (seen below before JSON ENCODE)
    Return: None

*/

function ajaxCTRload()
{
    //print_r($_REQUEST);
    $projectId = $_REQUEST['projectId'];
    //$projectId = "J18611046";

    list($startDate, $endDate) = getProjectDateDynamic($projectId);
    $dateInterval = getDateInterval($startDate, $endDate);
    //print_r(getProjectDate($projectId));
    //print("PERIOD {$startDate} - {$endDate}\n");
    //echo "<pre>";

    $currentBudgetJob = extractJobRow(getCurrentBudgetJob($projectId, $startDate, $endDate));
    $originalSchedule = generateOriginalSchedule(end($currentBudgetJob), $startDate, $endDate);
    $purchaseOrderDetails = getPODetails($projectId, $startDate, $endDate);

    $invoicedAmount = getInvoicedAmount($projectId, $startDate, $endDate);
    $invoicedAmountGroupByJob = extractJobRow(getInvoicedAmountGroupByJob($projectId, $startDate, $endDate));
    $invoicedAmountGroupBySubjob = getInvoicedAmountGroupBySubjob($projectId, $startDate, $endDate);
    $invoicedAmountGroupByTask = getInvoicedAmountGroupByTask($projectId, $startDate, $endDate);

    $reconciledAmountGroupById = getReconciledAmountGroupById($projectId, $startDate, $endDate);
    $reconciledAmountGroupByJob = extractJobRow(getReconciledAmountGroupByJob($projectId, $startDate, $endDate));
    $reconciledAmountGroupBySubjob = getInvoicedAmountGroupBySubjob($projectId, $startDate, $endDate);
    $reconciledAmountGroupByTask = getInvoicedAmountGroupByTask($projectId, $startDate, $endDate);

    $payrollGroupByEId = getPayrollGroupByEId($projectId, $startDate, $endDate);
    $payrollGroupByJob      = extractJobRow(getPayrollGroupByJob($projectId, $startDate, $endDate));
    $payrollGroupBySubjob       = getPayrollGroupBySubjob($projectId, $startDate, $endDate);
    $payrollGroupByTask     = getPayrollGroupByTask($projectId, $startDate, $endDate);

    $invoicedInGroupById        = getInvoicedInGroupById($projectId, $startDate, $endDate);
    $invoicedInGroupByJob       = getInvoicedInGroupByJob($projectId, $startDate, $endDate);

    $expensesGroupById      = getExpensesGroupById($projectId, $startDate, $endDate);
    $expensesGroupByJob     = getExpensesGroupByJob($projectId, $startDate, $endDate);
    $expensesGroupBySubjob      = getExpensesGroupBySubjob($projectId, $startDate, $endDate);
    $expensesGroupByTask        = getExpensesGroupByTask($projectId, $startDate, $endDate);

    $valueGroupByJob = extractJobRow(getValueGroupByJob($projectId, $startDate, $endDate));
    $valueGroupBySubjob = getValueGroupBySubjob($projectId, $startDate, $endDate);
    $valueGroupByTask = getValueGroupByTask($projectId, $startDate, $endDate);

    //JSON PACKAGING
    $outputJson = array(
        "startDate" => $startDate,
        "endDate" => $endDate,
        "dateInterval" => nullToEmpty($dateInterval),

        "currentBudgetJob" => nullToEmpty($currentBudgetJob),
        "originalSchedule" => nullToEmpty($originalSchedule),
        "purchaseOrderDetails" => nullToEmpty($purchaseOrderDetails),

        "invoicedAmountGroupById" => nullToEmpty($invoicedAmount),
        "invoicedAmountGroupByJob" => nullToEmpty($invoicedAmountGroupByJob),
        "invoicedAmountGroupBySubjob" => nullToEmpty($invoicedAmountGroupBySubjob),
        "invoicedAmountGroupByTask" => nullToEmpty($invoicedAmountGroupByTask),

        "reconciledAmountGroupById" => nullToEmpty($reconciledAmountGroupById),
        "reconciledAmountGroupByJob" => nullToEmpty($reconciledAmountGroupByJob),
        "reconciledAmountGroupBySubjob" => nullToEmpty($reconciledAmountGroupBySubjob),
        "reconciledAmountGroupByTask" => nullToEmpty($reconciledAmountGroupByTask),

        "payrollGroupByEId" => nullToEmpty($payrollGroupByEId),
        "payrollGroupByJob" => nullToEmpty($payrollGroupByJob),
        "payrollGroupBySubjob" => nullToEmpty($payrollGroupBySubjob),
        "payrollGroupByTask" => nullToEmpty($payrollGroupByTask),

        "invoicedInGroupById" => nullToEmpty($invoicedInGroupById),
        "invoicedInGroupByJob" => nullToEmpty($invoicedInGroupByJob),

        "expensesGroupById" => nullToEmpty($expensesGroupById),
        "expensesGroupByJob" => nullToEmpty($expensesGroupByJob),
        "expensesGroupBySubjob" => nullToEmpty($expensesGroupBySubjob),
        "expensesGroupByTask" => nullToEmpty($expensesGroupByTask),

        "valueGroupByJob" => nullToEmpty($valueGroupByJob),
        "valueGroupBySubjob" => nullToEmpty($valueGroupBySubjob),
        "valueGroupByTask" => nullToEmpty($valueGroupByTask)

    );

    $outputJson = json_encode($outputJson);
    print $outputJson;
    die();
}
// ajaxCTRload();
