<?php

/***** PHP Class: database - based on OCI plugin ******************************/
class database
{
    /***************************************************************************
     *  Member Variable Declarations
     **************************************************************************/
    // Connection Parameters
    protected $db;
    protected $db_name;
    protected $db_user;
    protected $db_pass;
    // Result Sets and Information
    public    $data_array;
    public    $data_table;
    public    $nrows;

    /***************************************************************************
     *  __construct - Default Constructor
     *  Description:  Resets all member values to null.
     *  Note:         Calls OLD STYLE constructor to reduce redundancy.
     **************************************************************************/
    public function __construct()
    {
        $this->database();
    } // __construct

    /***************************************************************************
     *  database - OLD STYLE Default Constructor (implemented for Legacy Apps)
     *  Description:  Resets all member values to null.
     **************************************************************************/
    public function database()
    {
        $this->db         = null;
        $this->db_name    = null;
        $this->db_user    = null;
        $this->db_pass    = null;
        $this->data_array = null;
        $this->data_table = null;
        $this->nrows      = null;
    } // database
    
    /***************************************************************************
     *  setConParam - Member Function - Public
     *  Description:  Allows other scripts to set (and alter) connection params.
     **************************************************************************/
    public function setConParam($db_name = null, $db_user = null, $db_pass = null)
    {
        $this->db_name = (empty($db_name)) ? $this->db_name : $db_name;
        $this->db_user = (empty($db_user)) ? $this->db_user : $db_user;
        $this->db_pass = (empty($db_pass)) ? $this->db_pass : $db_pass;
    } // setConParam

    /***************************************************************************
     *  db_connect - Member Function - Protected
     *  Description:  Create connection to database.
     *  Returns:      true or false (connection state)
     **************************************************************************/
    protected function db_connect($db_name = null, $db_user = null, $db_pass = null)
    {
        /***** Validate parameters OR use defaults *****/
        if (empty($db_name) || empty($db_user) || empty($db_pass)) {
            $db_name = $this->db_name;
            $db_user = $this->db_user;
            $db_pass = $this->db_pass;
        }
        
        /***** Validate database connection OR create new connection *****/
        if (!$this->db) {
            $this->db = oci_connect($db_user, $db_pass, $db_name);
        }
        if ($this->db)  {
            // Set date format for any application that is using this class
            $this->db_transact("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            return true;
        }
        return false;
    } // db_connect
    
    /***************************************************************************
     *  db_transactResults - Member Function - Public
     *  Description:  Issue transactional statements to database which expect
     *                results.
     *  Parameters:   $statement: SQL Statement to be run. Bind example:
     *                             'SELECT * FROM DUAL WHERE ID = :bindKey'
     *                $parametersArray: Array(':bindKey' => 'bindValue');
     *                $tableOut: true / false - table output as $this->data_table
     **************************************************************************/
    public function db_transactResults($statement, $parametersArray = [], $tableOut = false)
    {
        if ($tableOut === true) {
            $parametersArray[':table'] = '';
        }
        
        $stid = oci_parse($this->db, $statement);
        
        // Loop to bind variables, options for table result
        if (!empty($parametersArray)) {
            foreach ($parametersArray as $bindKey => $bindValue) {
                if ($bindKey == ':table' && $tableOut === true) {
                    // Create a new cursor resource
                    $this->data_table = oci_new_cursor($this->db);
                    oci_bind_by_name($stid, $bindKey, $this->data_table, -1, OCI_B_CURSOR);
                } else {
                    oci_bind_by_name($stid, $bindKey, $parametersArray[$bindKey], -1);
                }
            }
        }
        
        // The OCI_NO_AUTO_COMMIT flag tells Oracle not to commit the INSERT immediately
        // Use OCI_DEFAULT as the flag for PHP <= 5.3.1.  The two flags are equivalent
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid); // For oci_execute errors pass the statement handle
            oci_rollback($this->db);
            error_log("Query error: ". $e['sqltext']);
            return false;
        }
        
        if ($tableOut === true) {
            $r = oci_execute($this->data_table, OCI_NO_AUTO_COMMIT);
            if (!$r) {
                $e = oci_error($stid); // For oci_execute errors pass the statement handle
                oci_rollback($this->db);
                error_log("Query error: ". $e['sqltext']);
                return false;
            }
            $this->nrows = oci_fetch_all($this->data_table, $this->data_array, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        } else {
            $this->nrows = oci_fetch_all($stid, $this->data_array, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        }
        
        // Commit the changes to both tables
        $r = oci_commit($this->db);
        if (!$r) {
            $e = oci_error($this->db); // For oci_execute errors pass the statement handle
            error_log("Query error: ". $e['sqltext']);
            return false;
        }
        oci_free_statement($stid);
        return $this->data_array;
    } // db_transactResults

    /***************************************************************************
     *  db_transact - Member Function - Public
     *  Description:  Issue transactional statements to database.
     **************************************************************************/
    public function db_transact($statement, $parametersArray = [])
    {
        $stid = oci_parse($this->db, $statement);
        
        // The OCI_NO_AUTO_COMMIT flag tells Oracle not to commit the INSERT immediately
        // Use OCI_DEFAULT as the flag for PHP <= 5.3.1.  The two flags are equivalent
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid); // For oci_execute errors pass the statement handle
            oci_rollback($this->db);
            error_log("Query error: ". $e['sqltext']);
            return false;
        }

        // Commit the changes to both tables
        $r = oci_commit($this->db);
        if (!$r) {
            $e = oci_error($this->db); // For oci_execute errors pass the statement handle
            error_log("Query error: ". $e['sqltext']);
            return false;
        }
        oci_free_statement($stid);
        return true;
    } // db_transact

    /***************************************************************************
     *  __destruct - Default Destructor
     *  Description:  Ensures connection to database is closed.
     **************************************************************************/
    public function __destruct()
    {
        oci_close($this->db);
    } // __destruct
}