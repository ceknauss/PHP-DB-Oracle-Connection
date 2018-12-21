<?php

/***** PHP Class: database - based on PDO plugin ******************************/
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
     **************************************************************************/
    protected function db_connect($db_name = null, $db_user = null, $db_pass = null)
    {
        /***** Validate parameters OR use defaults *****/
        if (empty($db_name) || empty($db_user) || empty($db_pass)) {
            $db_name = $this->db_name;
            $db_user = $this->db_user;
            $db_pass = $this->db_pass;
        }

        try {
            $this->db = new PDO(
                $db_name,
                $db_user,
                $db_pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
                );
        } catch (PDOException $e) {
            die('ERROR: ' . $e->getMessage());
        }
    } // db_connect

    /***************************************************************************
     *  db_transactResults - Member Function - Public
     *  Description:  Issue transactional statements to database which expect
     *                results.
     **************************************************************************/
    public function db_transactResults($statement, $parametersArray = [])
    {
        try {
            $this->db->beginTransaction();
            $pStmt = $this->db->prepare($statement);
            if ($pStmt && !empty($parametersArray)) {
                $pStmt->execute($statement, $parametersArray);
            } elseif ($pStmt) {
                $pStmt->execute($statement);
            } else {
                die('ERROR: Statement failed Prepare.');
            }
            $this->db->commit();
            return $pStmt->fetchAll();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    } // db_transactResults

    /***************************************************************************
     *  db_transact - Member Function - Public
     *  Description:  Issue transactional statements to database which expect
     *                results.
     **************************************************************************/
    public function db_transact($statement, $parametersArray = [])
    {
        try {
            $this->db->beginTransaction();
            $pStmt = $this->db->prepare($statement);
            if ($pStmt && !empty($parametersArray)) {
                $pStmt->execute($statement, $parametersArray);
            } elseif ($pStmt) {
                $pStmt->execute($statement);
            } else {
                die('ERROR: Statement failed Prepare.');
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
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