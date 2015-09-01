<?php

/*
 * 
 */


#Check the available drivers on Operating System by uncommenting next line:
#print_r(PDO::getAvailableDrivers());

/**
 * 
 * 1) Offers the following non-MySQL Functions:
 *     1.1) Creates connection to an existing database.
 *     1.2) Checks if currently connected to a database.
 *     1.3) Checks if table exists.
 * 2) Supports the following MySQL Statements:
 *     2.1) MySQL Data Definition Statements:
 *         ALTER DATABASE
 *         ALTER TABLE			
 *         CREATE TABLE
 * 
 * The following drivers currently implement the PDO interface:
 *     | DRIVER NAME  | DATABASE NAME                              |  
 *  1. | PDO_CUBRID   |	Cubrid                                     |
 *  2. | PDO_DBLIB    |	FreeTDS / Microsoft SQL Server / Sybase    |
 *  3. | PDO_FIREBIRD |	Firebird                                   |
 *  4. | PDO_IBM      |	IBM DB2                                    |
 *  5. | PDO_INFORMIX |	IBM Informix Dynamic Server                |
 *  6. | PDO_MYSQL    |	MySQL 3.x/4.x/5.x                          |
 *  7. | PDO_OCI      |	Oracle Call Interface                      |
 *  8. | PDO_ODBC     |	ODBC v3 (IBM DB2, unixODBC and win32 ODBC) |
 *  9. | PDO_PGSQL    |	PostgreSQL                                 |
 * 10. | PDO_SQLITE   |	SQLite 3 and SQLite 2                      |
 * 11. | PDO_SQLSRV   |	Microsoft SQL Server / SQL Azure           |
 * 12. | PDO_4D	      | 4D                                         |
 * 
 * Caveats:
 *     1. Firebird connections are very different compared to the other databases. For simplicity, 
 *        MyPDO is designed to take all of the connection information for Firebird in under the
 *        $this->database variable.
 *        Examples:
 *            1.1. DSN example with path         : $this->database = "/path/to/DATABASE.FDB";
 *            1.2. DSN example with port and path: 
 *                 $this->database = "hostname/port:/path/to/DATABASE.FDB"
 *            1.3. DSN example with localhost and path to employee.fdb on Debian System:
 *                 "localhost:/var/lib/firebird/2.5/data/employee.fdb"
 * 
 *     2. PDO_IBM can connect using a connection string, or an .ini file. For simplicity,
 *        MyPDO is designed to only connect using a connection string. This string is created from
 *        $this->database, $this->server, $this->port, and $this->protocol.
 * 
 *     3. PDO_IBM can connect using a connection string, or an .ini file. For simplicity,
 *        MyPDO is designed to only connect using a connection string.
 * 
 *     4. PDO_OCI can connect using a database defined in tnsnames.ora or using the Oracle Instant
 *        Client. MyPDO supports both methods by creating a connection string using $this->server 
 *        and $this->database. The port number must be included in $this->server.
 *        Example:
 *            4.1 Database defined in tnsnames.ora: $this->database = "mydb";
 *            4.2 Oracle Instant Client: $this->server = "//localhost:1521"; $this->database = "mydb";
 * 
 *     5. PDO_SQLSRV if connecting to a SQL Azure database set $this->server to server information.
 *        Example:
 *            5.1 Server ID is 12345abcde. $this->server = "12345abcde.database.windows.net";
 * 
 *     6. PDO_4D is considered experimental, and it could recieve changes in future releases of PHP.
 *    
 
 */
class MyPDO {
    #Database Variable
    public $database_driver;
    
    #Connection Variables, some are specific to particular databases
    public $server                  ;
    public $database                ;
    public $username                ;
    public $password                ;
    public $port                    ; #Used for PDO_CUBRID, IBM, INFORMIX, ODBC, PGSQL, SQLSRV
    public $protocol                ; #Used for PDO_IBM, INFORMIX, ODBC
    public $host                    ; #Used for PDO_INFORMIX
    public $enable_scrollable_cursor; #Used for PDO_INFORMIX
    public $sqlite                  ; #Used for PDO_SQLITE
    public $create_db_in_memory     ; #Used for PDO_SQLITE
    
    #Non MySql Statements
    public $connection;
    public $is_connection_open;

    #Variables for error handling, one for each function
    public $open_connection_error; 
    public $does_table_exist_error;
    public $alter_database_error;
    public $create_table_error;
    public $alter_table_error;
    public $drop_table_error;
	
    #Will be called upon creation of $this object
    public function __construct($database_driver, $server = NULL, $database = NULL, $username = NULL,
            $password = NULL, $port = NULL, $protocol = NULL, $host = NULL, 
            $enable_scrollable_cursor = NULL, $sqlite = NULL, $create_db_in_memory = NULL) {
        $this->database_driver           = $database_driver        ;
        $this->server                    = $server                 ;
        $this->database                  = $database               ;
        $this->username                  = $username               ;
        $this->password                  = $password               ;
        $this->port                      = $port                   ;
        $this->protocol                  = $protocol               ;
        $this->host                      = $host                   ;
        $this->enable_scrollable_cursor = $enable_scrollable_cursor;
        $this->is_connection_open        = $this->checkConnection();
        $this->sqlite                    = $sqlite                 ;
        $this->create_db_in_memory       = $create_db_in_memory    ;
    }
    
    #Will be called when they are no other references to $this object
    public function __destruct() {
        if ($this->is_connection_open) $this->closeConnection();
    }
    
    /**
     * Enable the changing of overall characteristics of a database, these characteristics are stored
     * in the db.opt file in the database directory; you must have the ALTER privilege on the database.
     * 
     * Covers : MySQL Data Definition Statement: ALTER DATABASE
     * Synonym: ALTER SCHEMA is a synomym for ALTER DATABASE as of MySQL 5.0.2.
     * Syntax : https://dev.mysql.com/doc/refman/5.6/en/alter-database.html
     * 
     * Checks to see if there is an open connection to a database stored via 
     * $this->is_connection_open(). If connection is open, creates and executes a prepared statement 
     * within connection. If connection is closed, creates a new connection, creates and executes a 
     * preperared statement, then closes new connection.
     * 
     * If creating a new connection, will check to see if connection arguments were passed. If they were
     * they will overwrite existing connection information. If not existing connection information will
     * be used to create new connection.
     *  
     * Example Code:
     *      USE EXISTING CONNECTION:
     *          $connection->alterDatabase("character set", "collate");    
     *          
     *      CREATE NEW TEMPORARY CONNECTION:
     *         $connection = new PdoMySql();
     *         echo ($connection->alterDatabase("character_set", "collate", "server", "database name",
     *                 "username", "password") ? "TRUE" : "FALSE");
     *         unset($connection);
     * 
     * @param string $character_set string representing the default database character set
     * @param string $collate       string representing the default database collation
     * @param string $server        string containing server name or ip
     * @param string $database      string containing database name
     * @param string $username      string containing database username
     * @param string $password      string containing database password
     * @return boolean              boolean TRUE if ALTER DATABASE is successful, else FALSE
     */
    public function alterDatabase($character_set = NULL, $collate = NULL, $server = NULL,
            $database = NULL, $username = NULL, $password = NULL) {
        #Check if connection is open
        if($this->is_connection_open) {
            #Connection is open, create prepared statement, execute
            try {
                $prepared_statement_string                      = "ALTER DATABASE"               ;
                if ($character_set) $prepared_statement_string .= " CHARACTER SET $character_set";
                if ($collate)       $prepared_statement_string .= " COLLATE $collate"            ;
                $prepared_statement = $this->connection->prepare($prepared_statement_string);
                $prepared_statement->execute();                
            } catch(PDOException $pdo_exception) {
                #If connection fails, store error message
                $this->alter_database_error = handleExceptions($pdo_exception->getMessage());
            }
        #Connection is not open, create new connection, check table, close connection    
        } else {
            #If not specified, information is stored within $this
            if($server)   $this->server   = $server;
            if($database) $this->database = $database;
            if($username) $this->username = $username;
            if($password) $this->password = $password;

            $connection = new PdoMySql();
            $connection->openConnection($this->server, $this->database, $this->username,
                    $this->password);
            try {
                $prepared_statement_string                      = "ALTER DATABASE $database"     ;
                if ($character_set) $prepared_statement_string .= " CHARACTER SET $character_set";
                if ($collate)       $prepared_statement_string .= " COLLATE $collate"            ;
                $prepared_statement = $this->connection->prepare($prepared_statement_string);
                $prepared_statement->execute();	 			
            } catch(PDOException $pdo_exception) {
                #If connection fails, store error message
                $alter_database_error = handleExceptions($pdo_exception->getMessage());	 			
            }
            unset($connection);            
        }
        return ($alter_database_error ? "TRUE" : "FALSE");
    }
    
    /**
     * Changes the structure of a table; must have ALTER, CREATE, and INSERT privileges for the table.
     * 
     * Covers: MySQL Data Definition Statement: 13.1.7 ALTER TABLE Syntax
     * Syntax: https://dev.mysql.com/doc/refman/5.6/en/alter-table.html
     */
    public function alterTable($table, $sql, $server = null, $database = null, $username = null,
            $password = null) {
        #If not specified, use $this->connection
        if(!$server && !$database && !$username && !$password) {
            try {
                $prepared_statement = $this->connection->prepare("ALTER TABLE $table $sql");
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message				
                $this->alter_table_error = handleExceptions($pdo_exception->getMessage());				
            }
        #Specified, generate a new connection, create table, close connection.
        } else {
            $connection = new PdoMySql();
            $connection->openConnection($server, $database, $username, $password);
            try {
                $prepared_statement = $connection->connection->prepare("ALTER TABLE $table ($sql)");
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message
                $this->alter_table_error = handleExceptions($pdo_exception->getMessage());
            }
            unset($connection);
        }
        return ($this->alter_table_error ? FALSE : TRUE);
    }
    
    /**
     * Checks if $this object has an open connection to a database; sets $this->is_connection_open as
     * TRUE if connection is open, else FALSE.
     * 
     * Example Code:
     *     echo ($this->checkConnection() ? "Connection open\n" : "Connection closed\n");     
     * 
     * @return boolean  boolean TRUE if connection is open, else FALSE
     */
    public function checkConnection() {
        #Check if connection is currently empty
        if (empty($this->connection)) $this->is_connection_open = FALSE;
        #Connection not empty, check if connection contains an error string
        else $this->is_connection_open = ($this->open_connection_error ? FALSE : TRUE);
        return $this->is_connection_open;
    }

    /**
     * Checks if $this object has an open connection to a database via $this->is_connection_open, and
     * closes if open.
     * 
     * Example Code:
     *     echo ($connection->closeConnection() ? "Connection open\n" : "Connection closed\n");
     *
     * @return boolean boolean TRUE if there is no open connection, else FALSE
     */
    public function closeConnection() {
        if ($this->is_connection_open) unset($this->connection);
        #Check if connection was successfully closed; if open, closing failed
        return ($this->checkConnection() ? "FALSE" : "TRUE");
    }
    
    /**
     * 
     */
    public function createDatabase($database, $sql, $server = NULL, $existing_database = NULL,
            $username = NULL, $password = NULL) {
        
    }
    
    /**
     * Creates a new table with the given name, you must have the CREATE privilege for the table.
     * 
     * Covers: MySQL Data Definition Statement: CREATE TABLE
     * Syntax: Syntax: https://dev.mysql.com/doc/refman/5.6/en/create-table.html
     * 
     * If the current connection to the database is to be used, only send $table and $sql arguments;
     * a prepared statement will generate using the connection information in $this->connection.
     * 
     * If a new connection is needed, or a connection is not currently open, send all arguments ; a
     * prepared statement will generate using the connection information within the arguments. This
     * connection will be closed after creating the table.
     *  
     * There is no need to test if table already exists, as PDO Exception will catch that as an error.
     *
     * Also returns boolean value representing if the table was created successfully. This enables 
     * the ability to one line check, and then execute code based on that result.
     *  
     *  Example Code:
     *      USING EXISTING CONNECTION: 
     *          $connection = new PdoMySql();
     *          $connection->openConnection("server name", "database name", "username", "password");
     *          $connection->createTable("table name", "sql (e.g. firstName VARCHAR(30))");
     *          echo ($connection->doesTableExist("table name") ? "TRUE" : "FALSE");
     *          $connection->closeConnection();
     *          
     *          or (using result from function)
     *      	$connection = new PdoMySql();
     *          $connection->openConnection("server name", "database name", "username", "password");    
     *          echo ($connection->createTable("table name", "sql") ? "TRUE" : "FALSE");
     *          
     *      CREATING NEW TEMPORARY CONNECTION:
     *          $connection = new PdoMySql();
     *          $connection->createTable("table name", "sql", "server name", "database name",
     *                  "username", "password");
     *          echo ($connection->doesTableExist("table name", "server name", "database name",
     *                  "username", "password") ? "TRUE" : "FALSE");
     *      
     *          or (using result from function)
     *          $connection = new PdoMySql();
     *          echo ($connection->createTable("table name", "sql", "server name", "database name",
     *                  "username, "password") ? "TRUE" : "FALSE");
     *      
     * @param string $table    string containing database table name
     * @param string $server   string containing server name or ip
     * @param string $database string containing database name
     * @param string $username string containing database username
     * @param string $password string containing database password
     * @return boolean         boolean TRUE if table is successfully created, else FALSE 
     */
    public function createTable($table, $sql, $server = null, $database = null, $username = null,
            $password = null) {
        #If not specified, use $this->connection
        if(!$server && !$database && !$username && !$password) {
            try {
                $prepared_statement = $this->connection->prepare("CREATE TABLE $table ($sql)");
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message				
                $this->create_table_error = handleExceptions($pdo_exception->getMessage());				
            } finally {
                #Check to see if table was successfully created
                if($this->doesTableExist($table)) {					
                    return ($this->create_table_error ? FALSE : TRUE);
                } else {					
                    return FALSE;
                }
            }
        #Specified, generate a new connection, create table, close connection.
        } else {
            $connection = new PdoMySql();
            $connection->openConnection($server, $database, $username, $password);
            try {
                $prepared_statement = $connection->connection->prepare("CREATE TABLE $table ($sql)");
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message
                $this->create_table_error = handleExceptions($pdo_exception->getMessage());
            } finally {
                unset($connection);
                #Check to see if table was successfully created
                if($this->doesTableExist($table, $server, $database, $username, $password)) {
                    return ($this->create_table_error ? FALSE : TRUE);
                } else {
                    return FALSE;
                }
            }
        }
    }
    
    public function delete() {
        
    }
    
    /**
     * Validates the requested table exists within the specified database.
     * 
     * Checks to see if there is an open connection to a database stored via 
     * $this->is_connection_open(). If connection is open, creates and executes a prepared statement 
     * within connection. If connection is closed, creates a new connection, creates and executes a 
     * preperared statement, then closes new connection.
     * 
     * If creating a new connection, will check to see if connection arguments were passed. If they were
     * they will overwrite existing connection information. If not existing connection information will
     * be used to create new connection.
     * 
     * Example Code:
     *     USING EXISTING CONNECTION:
     *         echo ($connection->doesTableExist("table name") ? "TRUE" : "FALSE");
     * 
     *     CREATING NEW TEMPORARY CONNECTION:
     *         $connection = new PdoMySql();
     *         echo ($connection->doesTableExist("table name", "server name", "database name",
     *                 "username", "password") ? "TRUE" : "FALSE");
     *         unset($connection);
     *     
     * @param string $table    string containing database table name
     * @param string $server   string containing server name or ip
     * @param string $database string containing database name
     * @param string $username string containing database username
     * @param string $password string containing database password
     * @return boolean         boolean TRUE if table does exist, else FALSE
     */
    public function doesTableExist($table, $server = NULL, $database = NULL, $username = NULL,
        $password = NULL) {
        #Check if connection is open
        if($this->is_connection_open) {
            #Connection is open, create prepared statement, execute
            try {
                $prepared_statement = $this->connection->prepare("SELECT 1 FROM $table LIMIT 1" );
                $prepared_statement->execute();

                #Clear does_table_exist_error as table was found
                $this->does_table_exist_error = NULL;
            } catch(PDOException $pdo_exception) {
                #If connection fails, store error message
                $this->does_table_exist_error = handleExceptions($pdo_exception->getMessage());
            }            
        #Connection is not open, create new connection, check table, close connection    
        } else {
            #If not specified, information is stored within $this
            if($server)   $this->server   = $server;
            if($database) $this->database = $database;
            if($username) $this->username = $username;
            if($password) $this->password = $password;

            $connection = new PdoMySql();
            $connection->openConnection($this->server, $this->database, $this->username,
                    $this->password);
            try {
                $prepared_statement = $connection->connection->prepare("SELECT 1 FROM $table LIMIT 1");
                $prepared_statement->execute();
                #Clear does_table_exist_error as table was found
                $this->does_table_exist_error = NULL;
            } catch(PDOException $pdo_exception) {
                #If connection fails, store error message
                $this->does_table_exist_error = handleExceptions($pdo_exception->getMessage());
            }
            unset($connection);
        }
        return ($this->does_table_exist_error ? FALSE : TRUE);
    }
    
    public function dropDatabase() {
        
    }
    
    /**
     * Removes one or more tables from the specified database; you must have the DROP privilege for each
     * table.
     * 
     * Covers: 
     *     1. PDO_CUBRID: DROP TABLE
     *        http://www.cubrid.org/manual/93/en/sql/schema/table.html?highlight=drop%20table#drop-table
     * 
     *     2. PDO_DBLIB:
     *        Microsoft SQL Server: DROP TABLE
     *        https://msdn.microsoft.com/en-us/library/ms173790.aspx
     * 
     *        Sybase: DROP TABLE
     *        http://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.infocenter.dc36272.1572/html/commands/X58548.htm
     * 
     *     3. PDO_FIREBIRD: DROP TABLE
     *        http://www.firebirdsql.org/refdocs/langrefupd21-ddl-table.html
     * 
     *     4. PDO_IBM: DROP TABLE
     *        https://www-01.ibm.com/support/knowledgecenter/SSEPGG_9.7.0/com.ibm.db2.luw.admin.dbobj.doc/doc/t0005370.html
     * 
     *     5. PDO_INFORMIX: DROP TABLE
     *        http://www.pacs.tju.edu/informix/answers/english/docs/dbdk/is40/sqls/02drops13.html
     * 
     *     6: PDO_MYSQL: MySQL Data Definition Statement: DROP TABLE
     *        https://dev.mysql.com/doc/refman/5.6/en/drop-table.html
     * 
     *     7: PDO_OCI: DROP TABLE
     *        http://php.net/manual/en/function.oci-num-rows.php
     * 
     *     8: PDO_ODBC: DROP TABLE
     *        https://msdn.microsoft.com/en-us/library/ms714623(v=VS.85).aspx
     * 
     *     9: PDO_PGSQL: DROP TABLE
     *        http://www.postgresql.org/docs/8.2/static/sql-droptable.html
     * 
     *     10: PDO_SQLITE: DROP TABLE
     *         https://sqlite.org/lang_droptable.html
     * 
     *     11: PDO_SQLSRV: DROP TABLE
     *         https://msdn.microsoft.com/en-us/library/ms173790.aspx
     * 
     *     12: PDO_4D: DROP TABLE
     *         http://www.4d.com/4d_docv13/4D/13.4/DROP-TABLE.300-1225630.en.html
     *     
     * Checks to see if there is an open connection to a database stored via
     * $this->is_connection_open(). If connection is open, creates and executes a prepared statement
     * within connection. If connection is closed, creates a new connection, creates and executes a
     * preperared statement, then closes new connection.
     * 
     * If creating a new connection, will check to see if connection arguments were passed. If they were
     * they will overwrite existing connection information. If not existing connection information will
     * be used to create new connection.
     * 
     * All table data and the table definition are removed, so be careful with this statement! If any of
     * the tables named in the argument list do not exist, MySQL returns an error indicating by name
     * which nonexisting tables it was unable to drop, but it also drops all of the tables in the list
     * that do exist.
     * 
     * There is no error on when a table does not exist, as "IF EXISTS" will prevent deleting
     * non-existent tables. Therefore, $this->drop_table_error will not have an error for this. A value
     * of true simply means that no errors were encountered during "DROP TABLE".
     */
    public function dropTable($table, $server = NULL, $database = NULL, $username = NULL,
            $password = NULL, $port = NULL, $protocol = NULL, $host = NULL, 
            $enable_scrollable_cursor = NULL, $sqlite = NULL, $create_db_in_memory = NULL) {
        switch ($this->database_driver) {
            case "PDO_CUBRID"  :
            case "PDO_DBLIB"   :
            case "PDO_FIREBIRD":
            case "PDO_IBM"     : 
            case "INFORMIX"    :            
            case "PDO_MYSQL"   :
            case "PDO_OCI"     :
            case "PDO_PGSQL"   :
            case "PDO_SQLITE"  :
            case "PDO_SQLSRV"  :
            case "PDO_4D"      :
                $prepared_statement_string = "DROP TABLE IF EXISTS ";                
                if(gettype($table) !== "ARRAY") {
                    $prepared_statement_string .= "$table";
                } else {
                    $loop = 0;
                    foreach ($table as $element) {
                        if ($loop === 0) $prepared_statement_string .= "$element"  ;
                        else             $prepared_statement_string .= ", $element";
                    }
                }
                break;
        }
        
        #Check if connection is open
        if($this->is_connection_open) {
            #Connection is open, create prepared statement, execute
            try {
                $prepared_statement = $this->connection->prepare($prepared_statement_string);
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message
                $this->drop_table_error = handleExceptions($pdo_exception->getMessage());				
            }
        #Specified, generate a new connection, create table, close connection.
        } else {
            #If not specified, information is stored within $this
            if($server)                    $this->server                    = $server                ;
            if($database)                  $this->database                  = $database              ;
            if($username)                  $this->username                  = $username              ;
            if($password)                  $this->password                  = $password              ;
            if($port)                      $this->port                      = $port                  ;
            if($protocol)                  $this->protocl                   = $protocol              ;
            if($host)                      $this->host                      = $host                  ;
            if($enable_scrollable_cursor) $this->enable_scrollable_cursor = $enable_scrollable_cursor;
            if($sqlite)                    $this->sqlite                    = $sqlite                ;
            if($create_db_in_memory)       $this->create_db_in_memory       = $create_db_in_memory   ;
            
            #Create new MyPDO object
            $connection = new MyPDO($this->server, $this->database, $this->username, $this->password,
                    $this->port, $this->protocol, $this->host, $this->enable_scrollable_cursor,
                    $this->sqlite, $this->create_db_in_memory);
            #Open connection
            $connection->openConnection();
            
            try {
                $prepared_statement = $connection->connection->prepare($prepared_statement_string);
                $prepared_statement->execute();
            } catch(PDOException $pdo_exception) {
                #If creation fails, store error message
                $this->drop_table_error = handleExceptions($pdo_exception->getMessage());
            }
            unset($connection);
        }
        return ($this->drop_table_error ? FALSE : TRUE);
    }
    
    public function insert() {
        
    }

    /**
     * Establishes a new connection to a database, PDO does not support connecting to server without 
     * specifying a database.
     * 
     * Checks to see if connection arguments were passed. If they were, overwrite existing connection
     * information prior to establishing connection. If not, existing connection information will be
     * used to establish connection.
     *      
     * This function could be used to verify if a database exists.
     * 
     * Example Code:
     *      EXISTING CONNECTION INFORMATION
     *           $connection = new PdoMySql("server", "database", "username", "password");
     *           echo ($connection->openConnection() ? "TRUE" : "FALSE");
     * 
     *      SEND NEW CONNECTION INFORMATION
     *          $connection = new PdoMySql();
     *          echo ($connection->openConnection("server", "database", "username", "password")
     *                  ? "TRUE" : "FALSE");
     * 
     * @param string $server   string containing server name or ip
     * @param string $database string containing database name
     * @param string $username string containing database username
     * @param string $password string containing database password
     * @return boolean         boolean TRUE if connection to database was successful, else FALSE
     */
    public function openConnection($server = NULL, $database = NULL, $username = NULL, 
            $password = NULL, $port = NULL, $protocol = NULL, $host = NULL, 
            $enable_scrollable_cursor = NULL, $sqlite = NULL, $create_db_in_memory = NULL) {        
        #If not specified, information is stored within $this
        if($server)                    $this->server                    = $server                ;
        if($database)                  $this->database                  = $database              ;
        if($username)                  $this->username                  = $username              ;
        if($password)                  $this->password                  = $password              ;
        if($port)                      $this->port                      = $port                  ;
        if($protocol)                  $this->protocl                   = $protocol              ;
        if($host)                      $this->host                      = $host                  ;
        if($enable_scrollable_cursor) $this->enable_scrollable_cursor = $enable_scrollable_cursor;
        if($sqlite)                    $this->sqlite                    = $sqlite                ;
        if($create_db_in_memory)       $this->create_db_in_memory       = $create_db_in_memory   ;
        
        try {
            #Create string for prepared statement based on arguments and database driver
            switch ($this->database_driver) {
                case "PDO_CUBRID": 
                    $sql_string = "cubrid:dbname=$this->database;host=$this->server;port=$this->port";
                    break;
                
                case "PDO_DBLIB":
                    $sql_string = "dblib:host=$this->server;dbname=$this->database";
                    break;
                
                case "PDO_FIREBIRD":
                    $sql_string = "firebird:dbname=$this->database";
                    
                case "PDO_IBM":
                    $sql_string = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$this->database;"
                            . "HOSTNAME=$this->server;PORT=$this->port;PROTOCOL=$this->protocol;";
                    break;
                
                case "PDO_INFORMIX":
                    $sql_string = "informix:host=$this->host; service=$this->port; "
                            . "database=$this->database; server=$this->server; "
                            . "protocol=$this->protocol; " 
                            . "EnableScrollableCursors=$this->enable_scrollable_cursor";
                    break;
                    
                case "PDO_MYSQL": 
                    $sql_string = "mysql:host=$this->server;dbname=$this->database";
                    break;
                
                case "PDO_OCI":
                    if ($this->server) $sql_string = "oci:dbname=$this->server/$this->database;";
                    else $sql_string = "oci:dbname=$this->database;";
                    break;
                    
                case "PDO_ODBC":
                    if($this->server && $this->port && $this->protocol) {
                        $sql_string = "odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME=$this->server;"
                                . "PORT=$this->port;DATABASE=$this->database;PROTOCOL=$this->protocol;"
                                . "UID=$this->username;PWD=$this->password;";
                    } else {
                        $sql_string = "odbc:$this->database";
                    }
                    break;
                    
                case "PDO_PGSQL":
                    $sql_string = "pgsql:host=$this->server;port=$this->port;dbname=$this->database;"
                            . "user=$this->username;password=$this->password";
                    break;
                
                case "PDO_SQLITE":                    
                    $sql_string = "sqlite:$this->database.$this->sqlite";
                    break;
                
                case "PDO_SQLSRV":
                    $sql_string = "sqlsrv:Server=$this->server";
                    $sql_string .= ($this->port ? ",$this->port;" : ",;");
                    $sql_string .= "DATABASE=$this->database";
                    break;
                
                case "PDO_4D":
                    $sql_string = "host=$this->server";
            }
            
            #Open a new PDO connection
            if($this->database_driver !== "PDO_ODBC" 
                    || $this->database_driver !== "PDO_PGSQL" 
                    || $this->database_driver !== "PDO_SQLITE" 
                    || $this->database_driver !== "PDO_4D") {
                $this->connection = new PDO($sql_string, $this->username, $this->password);
            } else {
                $this->connection = new PDO($sql_string);
            }            

            #Set the PDO Error Mode to exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            #Clear open_connection_error as connection was successful
            $this->open_connection_error = NULL;
        } catch(PDOException $pdo_exception) {
            #If connection fails, store error message
            $this->open_connection_error = handleExceptions($pdo_exception->getMessage());
        } finally {
            #Check if connection was successfully opened
            $this->checkConnection();
        }
        #Was connection successful?
        return ($this->open_connection_error ? FALSE : TRUE);
    }    
    
    public function renameTable() {
        
    }
    
    public function replace() {
        
    }
    
    public function select() {
        
    }
    
    public function truncateTable() {
        
    }
}

function handleExceptions($exception) {
    #TODO: add logic	
    return $exception;
}

/**
 * Example Code: 
 *     #Create new MyPDO Object, using MYSQL driver, and storing connection information
 *     $connection = new MyPDO('PDO_MYSQL', 'localhost', 'test', 'root', 'ew4pkd8d');
 *   
 *     #Check if already connected to the database, at this point... this would always be false
 *     echo($connection->is_connection_open 
 *             ? "Connected to the database\n" : "Not connected to a database\n");
 * 
 *     #Connect to the database
 *     echo($connection->openConnection() ? "Connection successful\n" : "Connection unsuccessful\n");
 * 
 *     #Check if table 'test' exists, at this point... this should always be false
 *     echo ($connection->doesTableExist('test') ? "Table exists\n" : "Table does not exist\n");
 * 
 *     #Create table 'test'
 *     echo ($connection->createTable('test', 'firstName VARCHAR(30)') 
 *             ? "Table created\n" : "Table not created\n"); 
 * 
 *     #Add column to table 'test'
 *     echo ($connection->alterTable('test', 'ADD COLUMN lastName VARCHAR(30)') 
 *             ? "Column added\n" : "Column not added\n");
 * 
 *     #Drop table 'test'
 *     echo ($connection->dropTable('test') 
 *             ? "No errors during table dropping\n" : "Error during table dropping\n");
 * 
 *     unset($connection);
 */

#Create new MyPDO Object, using MYSQL driver, and storing connection information
$connection = new MyPDO('PDO_MYSQL', 'localhost', 'test', 'root', 'ew4pkd8d');

#Check if already connected to the database
echo ($connection->is_connection_open ? "Connected to database\n" : "Not connected to database\n");

#Connect to the database
echo ($connection->openConnection() ? "Connection successful\n" : "Connection unsuccessful\n");

#Check if table 'test' exists
echo ($connection->doesTableExist('test') ? "Table exists\n" : "Table does not exist\n");

#Create table 'test'
echo ($connection->createTable('test', 'firstName VARCHAR(30)') 
        ? "Table created\n" : "Table not created\n");

#Add column to table 'test'
echo ($connection->alterTable('test', 'ADD COLUMN lastName VARCHAR(30)') 
        ? "Column added\n" : "Column not added\n");

#Drop table 'test'
echo ($connection->dropTable('test') 
        ? "No errors during table dropping\n" : "Error during table dropping\n");

unset($connection);

#echo ($connection->is_connection_open ? "Connection Successful\n" : "Connection failed\n");
#
#if($connection->open_connection_error) echo $connection->open_connection_error;
#
#echo ($connection->doesTableExist('test') ? "Table exists\n" : "Table does not exist\n");
#
#echo ($connection->createTable('test', 'firstName VARCHAR(30)') 
#        ? "Table created\n" : "Table not created\n");
#
#echo ($connection->alterTable('test', 'ADD COLUMN lastName VARCHAR(30)') 
#        ? "Column Added\n" : "Column not added\n");
#echo $connection->alter_table_error;
#if ($connection->is_connection_open) $connection->closeConnection();
