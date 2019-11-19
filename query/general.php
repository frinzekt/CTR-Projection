<?php //COMMENT THIS FUNCTION WHEN DEPLOYED IN WP
function getConn()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sustech1_hourglass";


    // Create connection
    $conn = mysqli_connect($servername, $username, $password);
    $db = mysqli_select_db($conn, $dbname);

    return $conn;
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
