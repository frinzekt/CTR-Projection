<?php

if (is_admin()) {
    /* Graph magic ctr functiosnt */
    add_action('wp_ajax_ajax_CTRload', 'ajax_CTRload');
    add_action('wp_ajax_nopriv_ajax_CTRload', 'ajax_CTRload');
}


include "./general.php";
function sigmoid($t, $max)
{
    $shiftChange = $max / 2 / 10000;
    return $max / (1 + exp(-$shiftChange * $t));
}

function linspace($i, $f, $n)
{
    $step = ($f - $i) / ($n - 1);
    return range($i, $f, $step);
}

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
//GET A 1 VALUE RESULT
function fetchAggregateValue($sql)
{
    $conn = getConn();
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

//GET TABLE VALUES TO A STRUCTURE ARRAY
function fetchArrayValues($sql)
{
    $conn = getConn();
    $result = mysqli_query($conn, $sql);
    $table = mysqli_fetch_all($result);
    return $table;
}
// REVIEW  THIS IS UNTESTED
//USED FOR RUNNING TOTAL CALCULATIONS

function dayToStringConvert($day)
{
    return '+' . $day . 'day';
}

function getDateInterval($startDate, $endDate, $daysInterval = 7, $format = 'Y-m-d')
{

    $dates = [];

    $current = strtotime($startDate);
    $last = (($endDate != '0000-00-00') && ($endDate != "")) ? strtotime($endDate)
        : strtotime(dayToStringConvert($daysInterval * 10), $current);
    //$last = strtotime($endDate);

    $timeOffset = dayToStringConvert($daysInterval - 1);
    $daysInterval = dayToStringConvert($daysInterval);

    while ($current <= strtotime($timeOffset, $last)) {

        $dates[] = date($format, $current);
        $current = strtotime($daysInterval, $current);
    }
    /*else {
        $dates[] = strtotime($startDate);
        $dates[] = strtotime($daysInterval, $startDate);
    }*/
    return $dates;
}

function getProjectDate($projectId)
{
    $sql = "SELECT startdate,duedate FROM `jobs` WHERE jobnumber LIKE \"{$projectId}\"";

    $conn = getConn();
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($result) or die(mysqli_connect_error());
    // Row = [startdate,duedate]

    return $row;
}


function getCurrentBudgetJob($projectId, $startDate, $endDate)
{
    /*
    Return Structure
    | purchaseOrder | 6/05/2019 | 15/10/2019 |
    |---------------|-----------|------------|
    | 1             | 100       | 300        |
    | 2             | 50        | 200        |
    */

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

function getPODetails($projectId, $startDate, $endDate)
{
    /*
    Return Structure
    | purchaseOrder | 6/05/2019 | 15/10/2019 |
    |---------------|-----------|------------|
    | 1             | 100       | 300        |
    | 2             | 50        | 200        |
    */

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
function getInvoicedAmount($projectId, $startDate, $endDate)
{
    //Get Invoiced and  Reconcile Amount
    /*
eg. Structure
| invoiceID | 30/09/2019 | 31/10/2019 | Reconciled |
|-----------|------------|------------|------------|
| 3911012   | 1760       | 1760       | 1500       |
| 3911013   | 100        | 100        | 50         |
| 3911015   | 0          | 20         | 10         |
*/
    //GET ALL INVOICE IDs WITHIN THE DATE
    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceout.invoiceID");
    //print($sql);
    return fetchArrayValues($sql);
}

function getInvoicedAmountGroupByJob($projectId, $startDate, $endDate)
{
    //Get Invoiced and  Reconcile Amount
    /*
eg. Structure
| jobID     | 30/09/2019 | 31/10/2019 | Reconciled |
|-----------|------------|------------|------------|
| 3911012   | 1760       | 1760       | 1500       |

*/
    //GET ALL INVOICE IDs WITHIN THE DATE
    $sql = getInvoicedAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

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
function getReconciledAmountGroupById($projectId, $startDate, $endDate)
{
    /*Structure
        | invoiceInId | contractorId | 30/09/2019 | 31/10/2019 |
        |-------------|--------------|------------|------------|
        | i5          | 39           | 60         | 60         |
        | i6          | 39           | 60         | 60         |
    */
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.invoiceID");
    //print($sql);
    return fetchArrayValues($sql);
}
function getReconciledAmountGroupByJob($projectId, $startDate, $endDate)
{
    /*Structure
        | invoiceInId | contractorId | 30/09/2019 | 31/10/2019 |
        |-------------|--------------|------------|------------|
        | i5          | 39           | 60         | 60         |
        | i6          | 39           | 60         | 60         |
    */
    $sql = getReconciledAmountSQLTemplate($projectId, $startDate, $endDate, "invoiceunpaid.jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

/* Amount Spent
-> Employee
-> Contractor
-> Expenses
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
function getPayrollGroupByEId($projectId, $startDate, $endDate)
{  /* Structure
    | employeeID       | 27/08/2019 | 30/09/2019 |
    |------------------|------------|------------|
    | a@sustech.net.au | 2223.6     | 5559       |
    | b@sustech.net.au | 2223.6     | 5329       |
*/
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "employeeID");
    //print($sql);
    return fetchArrayValues($sql);
}

function getPayrollGroupByJob($projectId, $startDate, $endDate)
{  /* Structure
    | jobID          | 27/08/2019 | 30/09/2019 |
    |----------------|------------|------------|
    | 1              | 2223.6     | 5559       |
    | 2              | 2223.6     | 5329       |
*/
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "jobID");
    //print($sql);
    return fetchArrayValues($sql);
}

function getPayrollGroupBySubjob($projectId, $startDate, $endDate)
{
    /* Structure
    | subjobID       | 27/08/2019 | 30/09/2019 |
    |----------------|------------|------------|
    | 1              | 2223.6     | 5559       |
    | 2              | 2223.6     | 5329       |
*/
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "subjob");
    //print($sql);
    return fetchArrayValues($sql);
}

function getPayrollGroupByTask($projectId, $startDate, $endDate)
{
    /* Structure
    | subjobID       | taskID       | 27/08/2019 | 30/09/2019 |
    |----------------|----------------|------------|------------|
    | 1              | 1              | 2223.6     | 5559       |
    | 2              | 2              | 2223.6     | 5329       |
*/
    $sql = getPayrollSQLTemplate($projectId, $startDate, $endDate, "subjob,task");
    //print($sql);
    return fetchArrayValues($sql);
}

// CONTRACTOR / INVOICE IN
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

function getInvoicedInGroupById($projectId, $startDate, $endDate)
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

function getExpensesGroupById($projectId, $startDate, $endDate)
{
    /*Structure
    | expenseId | subjobId | 21/08/2019 | 20/08/2019 |
    |-----------|----------|------------|------------|
    | 2         |          | 1040       | 0          |
*/
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "expenseId,subjobId");
    //print($sql);
    return fetchArrayValues($sql);
}


function getExpensesGroupByJob($projectId, $startDate, $endDate)
{
    /*Structure
    | expenseId | subjobId | 21/08/2019 | 20/08/2019 |
    |-----------|----------|------------|------------|
    | 2         |          | 1040       | 0          |
*/
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "jobId");
    //print($sql);
    return fetchArrayValues($sql);
}

function getExpensesGroupBySubjob($projectId, $startDate, $endDate)
{
    /*Structure
    | expenseId | subjobId | 21/08/2019 | 20/08/2019 |
    |-----------|----------|------------|------------|
    | 2         |          | 1040       | 0          |
*/
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "subjobId");
    //print($sql);
    return fetchArrayValues($sql);
}
function getExpensesGroupByTask($projectId, $startDate, $endDate)
{
    /*Structure
    | subjobID       | Task      | 21/08/2019 | 20/08/2019 |
    |----------------|-----------|------------|------------|
    | 1              | 2         | 1040       | 0          |
*/
    $sql = getExpensesSQLTemplate($projectId, $startDate, $endDate, "subjobId,taskId");
    //print($sql);
    return fetchArrayValues($sql);
}

function extractJobRow($job)
{
    if (!empty($job)) {
        return $job[0];
    } else {
        return $job;
    }
}

function ajax_CTRload()
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
ajax_CTRload();
