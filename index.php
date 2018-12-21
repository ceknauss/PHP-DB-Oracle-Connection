<?php

/*******************************************************************************
// CSV direct download headers
// Uncomment block to force output of this file as CSV download
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=file.csv");
header("Pragma: no-cache");
header("Expires: 0");
//*****************************************************************************/

error_reporting(E_ALL);

// Use OCI if PDO not installed
require_once 'classes/class.database_oci.php';
//require_once 'classes/class.database_pdo.php';

class OracleConnect extends database
{
    public function __construct ()
    {
        parent::__construct();
        // Require Connection Parameters in Includes folder
        require('db_info.php'); // TNS Names
        require('db_conn_info.php'); // Database Connection Parameters
        // Set Connection Parameters
        $this->setConParam($db, $uname, $pword); // Variables in 'db_conn_info.php'
        $this->db_connect();
    }
}

// Create Database Class Object
$myDb = new OracleConnect();


// Oracle doesn't allow terminators in basic statements
$sqlStatement = "
    SELECT *
    FROM DUAL
";

$results = $myDb->db_transactResults(
    $sqlStatement // SQL Statement
);


// Oracle requires terminators in Procedure Calls
$sqlStatement = "
    BEGIN
        SCHEMA.sp_sampleTable_1( :stringVal, :table );
    END;
";

$results = $myDb->db_transactResults(
    $sqlStatement // SQL Statement
    ,array(
        ':stringVal' => 'This is my string'
    ) // Array of Parameters to pass as input
    ,true // Return table result as output to Assoc Array
);


// Generate CSV from Assoc Array
$myCSV = '';
$editRow = '';
foreach ($results as $recordKey => $record) {
    // Header Row
    $editRow = '';
    if ($recordKey === 0) {
        // IF first row, make column headers
        foreach ($record as $rowKey => $rowVal) {
            $editRow .= '"' . $rowKey . '",';
        }
        $myCSV .= substr($editRow, 0, -1) . "\n";
    }
    // Data Rows
    $editRow = '';
    foreach ($record as $rowKey => $rowVal) {
        $editRow .= '"' . $rowVal . '",';
    }
    $myCSV .= substr($editRow, 0, -1) . "\n";
}

// Output from database result
//print_r($results);

// Output from CSV Generation
print_r($myCSV);
