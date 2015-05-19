<?php

/* 
 * Copyright (c) 2015, Kevin Kabatra 
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * 1. Redistributions of source code must retain the above copyright notice, 
 *    this list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 */

/* 
 * The code follows the Follow Field Naming Conventions from the 
 * AOSP (Android Open Source Project) Code Style Guidelines for Contributors :
 *     Non-public, non-static field names start with m.
 *     Static field names start with s.
 *     Other fields start with a lower case letter.
 *     Public static final fields (constants) are ALL_CAPS_WITH_UNDERSCORES
 *     @link http://source.android.com/source/code-style.html
 */

    include_once 'get_post_parameter_fields_values.php';

    class PdoMysql {
        //Non MySQL Statements
        public $connection;
        public $isConnectionOpen;
        public $doesTableExist;
        public $doesDatabaseExist;
        
        //MySQL Database Definition Statements
        //1) ALTER DATABASE
        //2) ALTER FUNCTION
        //3) ALTER PROCEDURE
        //4) ALTER TABLE
        //5) ALTER VIEW
        public $wasDatabaseCreationSuccessful;
        //7) CREATE FUNCTION
        //8) CREATE INDEX
        //9) CREATE PROCEDURE and CREATE FUNCTION
        public $wasTableCreationSuccessful;
        //11) CREATE TRIGGER
        //12) CREATE VIEW
        //13) DROP DATABASE
        //14) DROP FUNCTION
        //15) DROP INDEX
        //16) DROP PROCEDURE and DROP FUNCTION
        //17) DROP TABLE
        //18) DROP TRIGGER
        //19) DROP VIEW
        //20) RENAME TABLE
        //21) TRUNCATE TABLE
        
        //MySQL Data Manipulation Statements
        //1)CALL
        public $wasRecordDeletionSuccessful;
        //3)DO
        //4)HANDLER
        public $wasRecordInsertionSuccessful;
        //6)LOAD DATA INFILE
        //7)REPLACE
        public $selected;
        //9)Subquery
        public $wasRecordUpdateSuccessful;
        
        //MySql Information Functions
        public $getLastInsertID;
        public $getRowCount;
        
        /**
         * 
         */
        public function __construct() {
            $this->isConnectionOpen = $this->isConnectionOpen();
       }
        
        /**
         * Attempts to create a new connection to an existing MySQL database.
         * 
         * If connection is successful, this->connection will be a PHP Data
         * Object (PDO). If the connection fails, this->connection will be a 
         * string containing an error message.
         * 
         * Example code:
         *     $connection = new PdoMysql();
         *     $connection->connectToDatabase('databaseName', 'serverName',
         *             'databaseUsername', 'databasePassword');
         *     if(gettype($connection->connection) === 'string') {
         *         //conenction failed, do something to process error message
         *         echo $connection->connection; 
         *     } else {
         *         //connection successful, now do something with the connection
         *         echo 'connection successful';
         *     }
         * 
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password         
         */
        public function connectToDatabase($mDatabaseName, $mServerName,
                $mDatabaseUsername, $mDatabasePassword) {
            try {
                $mPdoException = '';
                //Open a new PDO Connection
                $this->connection = new PDO("mysql:host=$mServerName;"
                        . "dbname=$mDatabaseName", $mDatabaseUsername,
                        $mDatabasePassword);
                //Set the PDO error mode to exception
                $this->connection->setAttribute(PDO::ATTR_ERRMODE,
                        PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $mPdoException) {
                $this->connection = handleExceptions(
                        $mPdoException->getMessage());            
            } finally {
                if(empty($mPdoException)) {
                    //do not need to do anything
                }
                $this->isConnectionOpen();
            }
        }
        
        /**
         * Validates open connection to database.
         * 
         * Example Code:
         *     if($connection->isConnectionOpen) {
         *         //connection is open
         *     } else {
         *         //connection is not open;
         *     }
         */
        public function isConnectionOpen() {
            if(!empty($this->connection)) {
                if(gettype($this->connection) !== 'string') {
                    $this->isConnectionOpen = TRUE;
                } else {
                    $this->isConnectionOpen = FALSE;
                }
            } else {
                $this->isConnectionOpen = FALSE;
            }
        }
        
        /**
         * If open, closes the connection to the database
         * 
         * Example Code:
         *     $connection->closeConnection();
         */
        public function closeConnection() {
            if($this->isConnectionOpen === TRUE) {
                unset($this->connection);
                $this->isConnectionOpen = FALSE;
            }
        }
        
        /**
         * Checks if specified table exists within specified database.
         * 
         * Example code:
         *     $connection = new PdoMysql();
         *     $connection->connectToDatabase($mDatabaseName, $mServerName, 
         *             $mDatabaseUsername, $mDatabasePassword);
         *     $connection->doesTableExist($mDatabaseName, $mServerName, 
         *             $mDatabaseUsername, $mDatabasePassword, $mTableName);
         *     echo 'does table exist: ' . $connection->doesTableExist;
         * 
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password
         * @param string $mTableName string containing table name
         */
        public function doesTableExist($mDatabaseName, $mServerName, 
                $mDatabaseUsername, $mDatabasePassword, $mTableName) {
            $mPdoException = NULL;
            try {
                $mConnection = new PdoMysql();
                $mConnection->connectToDatabase($mDatabaseName, $mServerName,
                        $mDatabaseUsername, $mDatabasePassword);
                if(gettype($mConnection) !== 'string') {
                    $mStatement = $mConnection->connection->prepare('SELECT 1 '
                            . ' FROM ' . $mTableName . ' LIMIT 1');
                    $mStatement->execute();
                } else {
                    $mPdoException = $mConnection;
                    $this->doesTableExist = $mConnection;
                }
            } catch(PDOException $mPdoException) {           
                $this->doesTableExist = FALSE;
            } finally {
                if($mPdoException === NULL) {
                    unset($mPdoException);
                    $this->doesTableExist = TRUE;
                } else {
                    $this->doesTableExist = handleExceptions(
                            $mPdoException->getMessage());
                }
           }
        }
       
        /**
         * Checks if specified database exists.
         * 
         * Example Code:
         *     $connection = new PdoMysql();
         *     $connection->connectToDatabase($mDatabaseName, $mServerName, 
         *             $mDatabaseUsername, $mDatabasePassword);
         *     $connection->doesDatabaseExist($mDatabaseName, $mServerName,
         *             $mDatabaseUsername, $mDatabasePassword);
         *     echo 'does database exist: ' . $connection->doesDatabaseExist;
         * 
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password
         */
        public function doesDatabaseExist($mDatabaseName, $mServerName, 
                $mDatabaseUsername, $mDatabasePassword) {
            $mPdoException = NULL;
            try {
                $mDatabaseExists = new PdoMysql();
                $mDatabaseExists->connectToDatabase($mDatabaseName,
                        $mServerName, $mDatabaseUsername, $mDatabasePassword);
                if(is_string($mDatabaseExists)) {
                    $this->doesDatabaseExist = FALSE;              
                } else {
                    $this->doesDatabaseExist = TRUE;
                }
            } catch(PDOException $mPdoException) {
                //do nothing with this
            } finally {
                if($mPdoException !== NULL) {
                    unset($mPdoException);
                    $this->doesDatabaseExist = handleExceptions(
                            $mPdoException->getMessage());
                }
            }    
       }
       
        /**
         * Creates a new table in a database.
         * 
         * Covers: MySQl Data Definition Statement: CREATE TABLE
         * Syntax: https://dev.mysql.com/doc/refman/5.0/en/create-table.html
         * 
         * Checks to see if the table to be created already exists. If 
         * $mTableExists is returned as type string, an error occurred while 
         * checking if the table existed; this will stop the table creation
         * process. 
         * 
         * If no error, next checks if $mTableExists is identical to TRUE, if so
         * stop the table creation process.
         * 
         * If $mTableExists is indetical to FALSE, attempts to create a 
         * new connection to specified database. If $mConnection is returned 
         * as type string, an error occured while  creating the connection 
         * (This should never be possible, as this->doesTableExist() creates a 
         * connection using the same method). 
         * 
         * If new connection successful attempts to create table using provided
         * table name, via $mTableName, and SQL via $mSql.
         * 
         * Example Code:
         *     $connection = new PdoMysql();
         *     $connection->createTable('databaseName', 'serverName',
         *             'databaseUsername', 'databasePassword', 'tableName',
         *             'firstName VARCHAR(30)');
         *     echo $connection->wasTableCreationSuccessful;
         * 
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password
         * @param string $mTableName string containing name of table to be
         *       created.
         * @param string $mSql string containing SQL code to generate Columns,
         *       Datatypes, etc.
         */
        public function createTable($mDatabaseName, $mServerName, 
                $mDatabaseUsername, $mDatabasePassword, $mTableName, $mSql) {
            try {
                $mTableExists = $this->doesTableExist($mDatabaseName,
                        $mServerName, $mDatabaseUsername, $mDatabasePassword,
                        $mTableName);
                if(gettype($mTableExists) !== 'string') {
                    if(!$mTableExists) {
                        try {
                            //Test database connection
                            $mConnection = new PdoMysql();
                            $mConnection->connectToDatabase($mDatabaseName,
                                    $mServerName, $mDatabaseUsername,
                                    $mDatabasePassword);
                            /*
                             * $mConnection at this point should never be a
                             * string, due to tableExists() creating a 
                             * connection using the same method. 
                             */
                            if(gettype($mConnection) !== 'string') {
                                $mStatement = $mConnection->connection->prepare(
                                        "CREATE TABLE $mTableName ($mSql)");
                                $mStatement->execute();
                            } else {
                                //connection failed
                                $this->wasTableCreationSuccessful = 
                                        $mConnection; 
                            }
                        } catch(PDOException $mPdoException) {
                            handleExceptions($mPdoException->getMessage());
                        } finally {
                            if(empty($mPdoException)) {
                                //table successfully created
                                $this->wasTableCreationSuccessful = TRUE;
                            } else {
                                $this->wasTableCreationSuccessful =
                                        handleExceptions($mPdoException->
                                                getMessage());
                            }
                        }
                    } else {
                        //table exists
                        $this->wasTableCreationSuccessful = FALSE;
                    }
                } else {
                    //table does not exist
                    $this->wasTableCreationSuccessful = $mTableExists;
                }
            } catch (PDOException $mPdoException) {
                $this->wasTableCreationSuccessful = handleExceptions(
                        $mPdoException->getMessage());
            }            
        }
        
        /**
         * Returns the ID of the last inserted row.
         * 
         * Covers: MySql Information Function: LAST_INSERT_ID()
         * Syntax: 
         * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html
         * 
         * Requires an AUTO_INCREMENT field.
         * 
         * Example code:
         *      $lastId = $connection->getLastInsertId;
         * 
         * @param type $mStatement
         */
        private function getLastInsertId($mStatement) {
            $this->getLastInsertId = $mStatement->lastInsertId();
        }
        
        /**
         * Returns the number of rows affected by the last DELETE, INSERT, or 
         * UPDATE statement executed by the corresponding PDOStatement object.
         * 
         * Covers: MySql Information Function: ROW_COUNT()
         * Syntax: 
         * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html
         * 
         * Example code:
         *      $rowCount = $connection->getRowCount;
         * 
         * @param type $mStatement
         */
        private function getRowCount($mStatement) {
            $this->getRowCount = $mStatement->rowCount();
        }
        
        /**
         * Used to insert record(s) into a MySQL database table.
         * 
         * Covers: MySQL Data Manipulation Statement: INSERT
         * Syntax: https://dev.mysql.com/doc/refman/5.0/en/insert.html
         * 
         * Example code:
         * <pre>$connection = new PdoMysql();
         * <pre>$connection = connectDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password');
         * <pre>$insertArray = array(
         * <pre>    1 => array(
         * <pre>        'key1' => 'value1',
         * <pre>    ),
         * <pre>    2 => array(
         * <pre>        'key1' => 'value1',
         * <pre>        'key2' => 'value2',
         * <pre>    ),
         * <pre>);
         * <pre>$connection->insertIntoDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password', 'tableName', $insertArray);
         * <pre>echo $connection->wasDatabaseInsertionSuccessful;
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password
         * @param string $mTableName string containing name of table to have
         *     record(s) inserted. 
         * @param type $mInsertArray array containing key/value pair(s) where 
         *     the key(s) represents field(s) within the specified database, 
         *     and the value(s) represents the values. 
         * @param boolean $mReturnLastInsertId optional boolean where true 
         *     calls $this->getLastInsertId().
         */
        function insertIntoDatabase($mDatabaseName, $mServerName,
                $mDatabaseUsername, $mDatabasePassword, $mTableName,
                $mInsertArray, $mReturnLastInsertId = FALSE) {
            try {
                $mPdoException = NULL;
                $mConnection = new PdoMysql();
                $mConnection->connectToDatabase($mDatabaseName, $mServerName,
                    $mDatabaseUsername, $mDatabasePassword);
                if(gettype($mConnection) !== 'string') {
                    if(count($mInsertArray) > 1) {
                        for($mLoop = 1; $mLoop <= count($mInsertArray);
                                $mLoop++) {
                            $mKey = $mValue = NULL;
                            foreach($mInsertArray[$mLoop] as $mKey => $mValue) {
                                $mStatement = $mConnection->connection->prepare(
                                        'INSERT INTO ' . $mTableName . ' (' 
                                        . getFields($mInsertArray[$mLoop]) 
                                        . ') Values ('
                                        . getBindFields($mInsertArray[$mLoop]) 
                                        . ')');
                                $mKey = $mValue = NULL;
                                foreach($mInsertArray[$mLoop] as $mKey => 
                                        $mValue) {
                                    $mStatement->bindValue(':' . $mKey,
                                            $mValue);
                                }
                                //row insertion
                                $mStatement->execute();
                            }
                        }
                    } elseif(count($mInsertArray) === 1) {
                        //prepare sql
                        $mStatement = $mConnection->connection->prepare(
                                'INSERT INTO ' . $mTableName . ' (' 
                                . getFields($mInsertArray[1]) . ') Values ('
                                . getBindFields($mInsertArray[1]) . ')');
                        $mKey = $mValue = NULL;
                        foreach($mInsertArray[1] as $mKey => $mValue) {
                            $mStatement->bindValue(':' . $mKey, $mValue);
                        }
                        //row insertion
                        $mStatement->execute();
                    } else {
                        //no parameters sent over
                        $this->wasRecordInsertionSuccessful = FALSE;
                    }
                }
            } catch(PDOException $mPdoException) {
               handleExceptions($mPdoException->getMessage());
            } finally {
                if(count($mInsertArray) >= 1 && empty($mPdoException)) {
                    if($mReturnLastInsertId) {
                        $this->wasRecordInsertionSuccessful = TRUE;
                        $this->getLastInsertId($mStatement);
                    } else {
                        $this->wasRecordInsertionSuccessful = TRUE;
                    }
                } else {
                    $this->wasRecordInsertionSuccessful = handleExceptions(
                       $mPdoException->getMessage());
                }
            }                       
        }
        
        /**
         * Used to delete record(s) from a MySQL database table.
         * 
         * Covers: MySQL Data Manipulation Statement: DELETE
         * Syntax: https://dev.mysql.com/doc/refman/5.0/en/delete.html
         * 
         * Example code:
         * <pre>$connection = new PdoMysql();
         * <pre>$connection = connectDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password');
         * <pre>$connection->deleteFromDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password', 'tableName');
         * <pre>echo 'row count: ' . $connection->getRowCount;
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password
         * @param string $mFrom string containing name of table to have
         *     record(s) deleted
         * @param string $mWhere optional string containing sql for WHERE clause
         * @param string $mOrderBy optional string containing sql for ORDER BY 
         *      clause
         * @param int $mLimit optional string containing sql for LIMIT clause
         */
        function deleteFromDatabase($mDatabaseName, $mServerName,
                $mDatabaseUsername, $mDatabasePassword, $mFrom, $mWhere = NULL,
                $mOrderBy = NULL, $mLimit = NULL) {
            try {
                $mPdoException = NULL;
                $mConnection = new PdoMysql();
                $mConnection->connectToDatabase($mDatabaseName, $mServerName,
                    $mDatabaseUsername, $mDatabasePassword);
                if(gettype($mConnection) !== 'string') {
                    $mSql = 'DELETE FROM ' . $mFrom;
                    if($mWhere !== NULL) {                        
                        $mSql .= ' WHERE ' . $mWhere;
                    }
                    if($mOrderBy !== NULL) {
                        $mSql .= ' ORDER BY ' . $mOrderBy;
                    }
                    if($mLimit !== NULL) {                        
                        $mSql .= ' LIMIT ' . $mLimit;
                    }
                    $mStatement = $mConnection->connection->prepare($mSql);
                    $mStatement->execute();
                    $this->getRowCount($mStatement);
                }
            } catch(PDOException $mPdoException) {
                handleExceptions($mPdoException->getMessage());
            } finally {
                if(empty($mPdoException)) {
                    $this->wasRecordDeletionSuccessful = TRUE;
                } else {
                    $this->wasRecordDeletionSuccessful = handleExceptions(
                            $mPdoException->getMessage());
                }
            }
        }
        
        /**
         * Selects record(s) from specified database
         * 
         * Covers: MySQL Data Manipulation Statement: SELECT
         * Syntax: https://dev.mysql.com/doc/refman/5.0/en/select.html
         * 
         * Example Code:
         * <pre>$connection = new PdoMysql();
         * <pre>$connection = connectDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password');
         * <pre>$connection->selectFromDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password', 'databaseName.tableName');
         * <pre>echo var_dump($connection->selected);
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password         
         * @param string $mTableReference string containing name of database and
         *     table to  have record(s) selected
         * @param string $mSelect string containing name of columns to include 
         *     in result set
         * @param string $mWhere optional string containing sql for WHERE clause
         * @param string $mGroupBy optional string containing sql for GROUP BY
         *     clause
         * @param string $mHaving optional string containing sql for HAVING
         *     clause
         * @param string $mOrderBy optional string containing sql for ORDER BY 
         *     clause
         * @param int $mLimit optional string containing sql for LIMIT clause
         */
        public function selectFromDatabase($mDatabaseName, $mServerName,
                $mDatabaseUsername, $mDatabasePassword, $mTableReference,
                $mSelect = '*', $mWhere = NULL, $mGroupBy = NULL,
                $mHaving = NULL, $mOrderBy = NULL, $mLimit = NULL) {
            try {
                $mPdoException = NULL;
                $mConnection = new PdoMysql();
                $mConnection->connectToDatabase($mDatabaseName, $mServerName,
                    $mDatabaseUsername, $mDatabasePassword);
                if(gettype($mConnection) !== 'string') {
                    $mSql = 'SELECT '. $mSelect . ' FROM ' . $mTableReference;
                    if($mWhere !== NULL) {
                        $mSql .= ' WHERE ' . $mWhere;
                    }
                    if($mGroupBy !== NULL) {
                        $mSql .= ' GROUP BY ' . $mGroupBy;
                    }
                    if($mHaving !== NULL) {
                        $mSql .= ' HAVING ' . $mHaving;
                    }
                    if($mOrderBy !== NULL) {
                        $mSql .= ' ORDER BY ' . $mOrderBy;
                    }
                    if($mLimit !== NULL) {
                        $mSql .= ' LIMIT ' . $mLimit;
                    }
                    $mStatement = $mConnection->connection->prepare($mSql);
                    $mStatement->execute();
                    $mStatement->setFetchMode(PDO::FETCH_ASSOC);
                    $this->selected = $mStatement->fetchAll();
                }
            } catch(PDOException $mPdoException) {
                $this->selected = handleExceptions(
                        $mPdoException->getMessage());
            } 
        }
        
        /**
         * Updates records from specified database.
         * 
         * Covers: MySql Data Manipulation Statement: UPDATE
         * Syntax: https://dev.mysql.com/doc/refman/5.0/en/update.html
         * 
         * Example Code:
         * <pre>$connection = new PdoMysql();
         * <pre>$connection = connectDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password');
         * <pre>$connection->updateWithinDatabase('databaseName', 'serverName',
         * <pre>        'username', 'password', 'databaseName.tableName',
         * <pre>        'field1 = \'value1\'');
         * <pre>echo $connection->getRowCount;
         * @param string $mDatabaseName string containing database name
         * @param string $mServerName string containing server name
         * @param string $mDatabaseUsername string containing database username
         * @param string $mDatabasePassword string containing database password         
         * @param string $mTableReference string containing name of database and
         *     table to  have record(s) updated
         * @param string $mSet string containing swl for SET clause
         * @param string $mWhere optional string containing sql for WHERE clause
         * @param string $mOrderBy optional string containing sql for ORDER BY 
         *     clause
         * @param int $mLimit optional string containing sql for LIMIT clause
         */
        public function updateWithinDatabase($mDatabaseName, $mServerName,
                $mDatabaseUsername, $mDatabasePassword, $mTableReference,
                $mSet, $mWhere = NULL, $mOrderBy = NULL, $mLimit = NULL) {
            try {
                $mPdoException = NULL;
                $mConnection = new PdoMysql();
                $mConnection->connectToDatabase($mDatabaseName, $mServerName,
                    $mDatabaseUsername, $mDatabasePassword);
                if(gettype($mConnection) !== 'string') {
                    $mSql = 'UPDATE '.  $mTableReference . ' SET ' . $mSet;
                    if($mWhere !== NULL) {
                        $mSql .= ' WHERE ' . $mWhere;
                    }
                    if($mOrderBy !== NULL) {
                        $mSql .= ' ORDER BY ' . $mOrderBy;
                    }
                    if($mLimit !== NULL) {
                        $mSql .= ' LIMIT ' . $mLimit;
                    }
                    $mStatement = $mConnection->connection->prepare($mSql);
                    $mStatement->execute();
                    $this->getRowCount($mStatement);
                }
            } catch(PDOException $mPdoException) {
                handleExceptions($mPdoException->getMessage());
            } finally {
                if(empty($mPdoException)) {
                    $this->wasRecordUpdateSuccessful = TRUE;
                } else {
                    $this->wasRecordUpdateSuccessful = handleExceptions(
                            $mPdoException->getMessage());
                }
            }
        }
    }
    
    /**
     * 
     * @param type $mPostParameter
     * @return type
     */
    function getFields($mPostParameter) {        
        $mFields = getPostParameterFieldsValues("field", $mPostParameter);
        return $mFields;
    }
    
    /**
     * 
     * @param type $mPostParameter
     * @return type
     */
    function getValues($mPostParameter) {
        $mValues = getPostParameterFieldsValues("value", $mPostParameter);
        return $mValues;
    }    
    
    /**
     * 
     * @param type $mPostParameter
     * @return type
     */
    function getBindFields($mPostParameter) {
        $mBindFields = getPostParameterBindFields($mPostParameter);
        return $mBindFields;
    }
    
    /**
     * 
     * @param type $mPdoException
     */
    function handleExceptions($mPdoException) {        
        if(strpos($mPdoException, "SQLSTATE[23000]") !== false) {
            echo "Error: Duplicate entry.<br>";
            if(strpos($mPdoException, "'subject'") !== false) {
                //TODO: Provide a link to recover account
                echo "Error: that username already exists.<br>";
            } else if(strpos($mPdoException, "'id'") !== false) {
                //TODO: Provide logging to a developer console
                //critical error, the database did not automatically auto increment                
            }            
        }
        
        if(strpos($mPdoException, "SQLSTATE[28000]") !== false) {
            //TODO: Provide logging to a developer console
            //critical error, the database did not log in using username and password
            return 'Error[28000]: Could not authenticate username and password';
        }
        
        if(strpos($mPdoException, "SQLSTATE[42000]") !== false) {
            //TODO: Provide logging to a developer console
            //You have an error in your SQL syntax
            return 'Error[42000]: You have an error in your SQL syntax.';
        }
        return $mPdoException;
    }