<?php

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
    Output: 
        - 
    Return: Value between 0 to max (float)
*/
function sigmoid($t, $max)
{
    $shiftChange = $max / 2 / 10000;
    return $max / (1 + exp(-$shiftChange * $t));
}


/*
    Inputs:
        - start (number)- start of the array to be created
        - end (number) - end of the array to be created
        - $n (int)- number of terms
    Action: Creates an Array of $n terms equally spaced between start and end
    Output: 
        - 
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
    Output: 
        - 
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
    Output: 
        - 
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
    Output: 
        - 
    Return: returns the whole table generated from the sql fetch (Array)
*/
function fetchArrayValues($sql)
{
    $conn = getConn();
    $result = mysqli_query($conn, $sql);
    $table = mysqli_fetch_all($result);
    return $table;
}

// USED FOR TURNING DAY INTO A STRING SUITABLE FOR STRTOTIME CONVERTION
/*
    Inputs:
        - $day (int)
    Action: Converts the day number into a STR that is convertible using strtotime()
    Output: 
        - 
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
    Output: 
        - 
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
function getProjectDate($projectId)
{
    $sql = "SELECT startdate,duedate FROM `jobs` WHERE jobnumber LIKE \"{$projectId}\"";

    $conn = getConn();
    $result = mysqli_query($conn, $sql);
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
    Output: 
        - 
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

    $sql .= "FROM `purchaseorders` WHERE jobId=\"{$projectId}\"";

    //print($sql);
    return fetchArrayValues($sql);
}


/*
    Inputs:
        - $projectId (string)
        - $startDate (string - Date)
        - $endDate (string - Date)
    Action: Gets the Purchase Order Values from the purchase Order Table
    Output: 
        - 
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

    $sql .= "FROM `purchaseorders` WHERE jobId=\"{$projectId}\"\n"
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
        - 
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
        $sql .= "SUM( CASE WHEN invoiceout.periodEnd<=\"{$date}\" THEN \n"
            . "invoiceunpaid.number*invoiceunpaid.price ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoiceout` \n"
        . "INNER JOIN invoiceunpaid On invoiceout.invoiceID = invoiceunpaid.invoiceID \n"
        . "WHERE invoiceunpaid.jobID=\"{$projectId}\"\n"
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
        - 
    Return:  (Array)
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
    | 3911015   | 0          | 20         |
*/
function getInvoicedAmount($projectId, $startDate, $endDate)
{
    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceout.invoiceID");
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
        - 
    Return:  (Array)
    | jobID     | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/

function getInvoicedAmountGroupByJob($projectId, $startDate, $endDate)
{

    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
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
        - 
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
        $sql .= "SUM( CASE WHEN invoiceout.periodEnd<=\"{$date}\" THEN \n"
            . "invoiceunpaid.reconciled ELSE 0 END) AS \"{$date}\"";

        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoiceout` \n"
        . "INNER JOIN invoiceunpaid On invoiceout.invoiceID = invoiceunpaid.invoiceID \n"
        . "WHERE invoiceunpaid.jobID=\"{$projectId}\"\n"
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
        - 
    Return:  (Array)
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/
function getReconciledAmountGroupById($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.invoiceID");
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
    | invoiceID | 30/09/2019 | 31/10/2019 |
    |-----------|------------|------------|
    | 3911012   | 1760       | 1760       |
    | 3911013   | 100        | 100        |
*/
function getReconciledAmountGroupByJob($projectId, $startDate, $endDate)
{
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
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
    $sql .= "FROM `payroll` WHERE jobID=\"{$projectId}\"\n"
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
        $sql .= "SUM( CASE WHEN invoicein.periodEnd<=\"$date\" THEN \n"
            . "invoiceunpaid.number*invoiceunpaid.price ELSE 0 END) AS \"$date\"";
        if (++$i === $numItems) { // LAST INDEX
            $sql .= "\n";
        } else {
            $sql .= ",\n";
        }
    }

    $sql .= "FROM `invoicein`\n"
        . "INNER JOIN invoiceunpaid ON\n"
        . "invoicein.invoiceInId=invoiceunpaid.invoiceID\n"
        . "WHERE invoiceunpaid.jobID=\"$projectId\"\n"
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
    $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.invoiceInId");
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
    $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
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
//     $sql = getInvoicedInSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
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
        $sql .= "SUM(CASE WHEN dateOfExpense<=\"2019-08-21\" THEN netCost ELSE 0 END) AS \"2019-08-21\"";
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
        - 
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
        - 
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
        - 
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
        - 
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
        - $job array (2D from SQL)
    Action: Since all the queries with job rows are only 1D Array yet SQL gives 2D Array, API packaging is better with 1D array for the purpose of easy extraction in frontend
    Dependencies:
        - 
    Output:
        - 
    Return: $job row (1D array), if the $jo empty, returns an empty array
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
    array(
        "startDate" => $startDate,
        "endDate" => $endDate,
        "dateInterval" => $dateInterval,
        "currentBudgetJob" => $currentBudgetJob,
        "originalSchedule" => $originalSchedule,
        "purchaseOrderDetails" => $purchaseOrderDetails,
        "invoicedAmountGroupById" => $invoicedAmount,
        "invoicedAmountGroupByJob" => $invoicedAmountGroupByJob,
        "reconciledAmountGroupById" => $reconciledAmountGroupById,
        "reconciledAmountGroupByJob" => $reconciledAmountGroupByJob,
        "payrollGroupByEId" => $payrollGroupByEId,
        "payrollGroupByJob" => $payrollGroupByJob,
        "payrollGroupBySubjob" => $payrollGroupBySubjob,
        "payrollGroupByTask" => $payrollGroupByTask,
        "invoicedInGroupById" => $invoicedInGroupById,
        "invoicedInGroupByJob" => $invoicedInGroupByJob,
        "expensesGroupById" => $expensesGroupById,
        "expensesGroupByJob" => $expensesGroupByJob,
        "expensesGroupBySubjob" => $expensesGroupBySubjob,
        "expensesGroupByTask" => $expensesGroupByTask
    );
    Return: None

*/
function ajaxCTRload()
{
    //print_r($_REQUEST);
    $projectId = $_REQUEST['projectId'];
    //$projectId = "J18611046";

    list($startDate, $endDate) = getProjectDate($projectId);
    $dateInterval = getDateInterval($startDate, $endDate);
    //print_r(getProjectDate($projectId));
    //print("PERIOD {$startDate} - {$endDate}\n");
    //echo "<pre>";

    //print("CurrentBudgetJob\n");
    $currentBudgetJob = extractJobRow(getCurrentBudgetJob($projectId, $startDate, $endDate));
    $originalSchedule = generateOriginalSchedule(end($currentBudgetJob), $startDate, $endDate);
    $purchaseOrderDetails = getPODetails($projectId, $startDate, $endDate);
    //print("InvoicedAmount\n");
    $invoicedAmount = getInvoicedAmount($projectId, $startDate, $endDate);
    $invoicedAmountGroupByJob = extractJobRow(getInvoicedAmountGroupByJob($projectId, $startDate, $endDate));
    $reconciledAmountGroupById = getReconciledAmountGroupById($projectId, $startDate, $endDate);
    $reconciledAmountGroupByJob = extractJobRow(getReconciledAmountGroupByJob($projectId, $startDate, $endDate));
    //print("PayrollGroupByEId\n");
    $payrollGroupByEId = getPayrollGroupByEId($projectId, $startDate, $endDate);
    //print("PayrollGroupByJob\n");
    $payrollGroupByJob      = extractJobRow(getPayrollGroupByJob($projectId, $startDate, $endDate));
    //print("PayrollGroupBySubjob\n");
    $payrollGroupBySubjob       = getPayrollGroupBySubjob($projectId, $startDate, $endDate);
    //print("PayrollGroupByTask(\n");
    $payrollGroupByTask     = getPayrollGroupByTask($projectId, $startDate, $endDate);
    //print("InvoicedInGroupById\n");
    $invoicedInGroupById        = getInvoicedInGroupById($projectId, $startDate, $endDate);
    //print("InvoicedInGroupByJob\n");
    $invoicedInGroupByJob       = getInvoicedInGroupByJob($projectId, $startDate, $endDate);
    //print("ExpensesGroupById\n");
    $expensesGroupById      = getExpensesGroupById($projectId, $startDate, $endDate);
    //print("ExpensesGroupByJob\n");
    $expensesGroupByJob     = getExpensesGroupByJob($projectId, $startDate, $endDate);
    //print("ExpensesGroupBySubjob\n");
    $expensesGroupBySubjob      = getExpensesGroupBySubjob($projectId, $startDate, $endDate);
    //print("ExpensesGroupByTask\n");
    $expensesGroupByTask        = getExpensesGroupByTask($projectId, $startDate, $endDate);


    $outputJson = array(
        "startDate" => $startDate,
        "endDate" => $endDate,
        "dateInterval" => $dateInterval,
        "currentBudgetJob" => $currentBudgetJob,
        "originalSchedule" => $originalSchedule,
        "purchaseOrderDetails" => $purchaseOrderDetails,
        "invoicedAmountGroupById" => $invoicedAmount,
        "invoicedAmountGroupByJob" => $invoicedAmountGroupByJob,
        "reconciledAmountGroupById" => $reconciledAmountGroupById,
        "reconciledAmountGroupByJob" => $reconciledAmountGroupByJob,
        "payrollGroupByEId" => $payrollGroupByEId,
        "payrollGroupByJob" => $payrollGroupByJob,
        "payrollGroupBySubjob" => $payrollGroupBySubjob,
        "payrollGroupByTask" => $payrollGroupByTask,
        "invoicedInGroupById" => $invoicedInGroupById,
        "invoicedInGroupByJob" => $invoicedInGroupByJob,
        "expensesGroupById" => $expensesGroupById,
        "expensesGroupByJob" => $expensesGroupByJob,
        "expensesGroupBySubjob" => $expensesGroupBySubjob,
        "expensesGroupByTask" => $expensesGroupByTask
    );

    $outputJson = json_encode($outputJson);
    print $outputJson;
    //echo "</pre>";
}
// ajaxCTRload();
