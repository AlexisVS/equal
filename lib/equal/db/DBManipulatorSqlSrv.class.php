<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\db;

/**
 * DBManipulator implementation for MS SQL server.
 *
 */

class DBManipulatorSqlSrv extends DBManipulator {


    public static $types_associations = [
        'boolean'       => 'bit',
        'integer'       => 'int',
        'float'         => 'float(24)',
        'string'        => 'varchar(255)',
        'text'          => 'ntext',
        'date'          => 'date',
        'time'          => 'time',
        'datetime'      => 'datetime2',
        'timestamp'     => 'int',
        'file'          => 'image',
        'binary'        => 'image',
        'many2one'      => 'int'
    ];

    public function getSqlType($type) {
        if(isset(self::$types_associations[$type])) {
            return self::$types_associations[$type];
        }
        return '';
    }

    /**
     * This method has no effect.
     * There is no distinction between select and conenct.
     */
    public function select($db_name) {
        return $this->db;
    }

    /**
     * Open the DBMS connection
     *
     * @param   boolean   $auto_select	Automatically connect to provided database (otherwise the connection is established only wity the DBMS server)
     * @return  integer   		        The status of the connect function call.
     * @access  public
     */
    public function connect($auto_select=true) {
        $result = false;
        if($this->canConnect($this->host, $this->port) && function_exists('sqlsrv_connect')) {
            $connection_info = [
                    "UID"                   => $this->user_name,
                    "PWD"                   => $this->password,
                    "Database"              => $this->db_name,
                    // Possible values are SQLSRV_ENC_CHAR and UTF-8, we set to utf-8 whatever the DB_COLLATION setting
                    'CharacterSet'          => 'utf-8',
                    // allow connection to server with self signed SSL certificate
                    "TrustServerCertificate"=> true
                ];

            if($this->dbms_handler = sqlsrv_connect($this->host, $connection_info)) {
                $result = true;
            }

            foreach($this->members as $member) {
                if($member->connect($auto_select) === false) {
                    $result = false;
                    break;
                }
            }
        }
        if(!$result) {
            return false;
        }
        return $this;
    }

    /**
     * Close the DBMS connection
     *
     * @return   integer   Status of the close function call
     * @access   public
     */
    public function disconnect() {
        if(isset($this->dbms_handler)) {
            sqlsrv_close($this->dbms_handler);
            $this->dbms_handler = null;
            foreach($this->members as $member) {
                $member->disconnect();
            }
        }
        return true;
    }

    /**
     * Sends a SQL query.
     *
     * @param string The query to send to the DBMS.
     *
     * @return resource Returns a resource identifier or -1 if the query was not executed correctly.
     */
    function sendQuery($query) {
        trigger_error("QN_DEBUG_SQL::$query", E_USER_NOTICE);

        if(($result = sqlsrv_query($this->dbms_handler, $query)) === false) {
            $error_str = '';
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    $error_str .= implode(',', $error);
                }
            }
            throw new \Exception(__METHOD__.' : query failure. '.$error_str.'. For query: "'.$query.'"', QN_ERROR_SQL);
        }
        // everything went well: perform additional operations (replication & info about query result)
        else {
            // update $affected_rows, $last_query, $last_id (depending on the performed operation)
            $sql_operation = strtolower((explode(' ', $query, 2))[0]);
            $this->setLastQuery($query);
            if($sql_operation == 'select') {
                $this->setAffectedRows(sqlsrv_num_rows($result));
            }
            else {
                // for WRITE operations, relay query to members of the replica
                if(in_array($sql_operation, ['insert', 'update', 'delete', 'drop', 'create'])) {
                    foreach($this->members as $member) {
                        $member->sendQuery($query);
                    }
                }
                if($sql_operation =='insert') {
                    if($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                        $this->setLastId($row['id']);
                    }
                }
                $this->setAffectedRows(sqlsrv_rows_affected($this->dbms_handler));
            }
        }
        return $result;
    }

    public static function fetchRow($result) {
        return sqlsrv_fetch($result);
    }

    public static function fetchArray($result) {
        return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Escapes a string containing the name of an object's field to match the SQL notation : `table`.`field` or `field`
     *
     * @param string $field_name
     * @return string
     */
    private static function escapeFieldName($field_name) {
        $parts = explode('.', str_replace('`', '', $field_name));
        return (count($parts) > 1)?"[{$parts[0]}].[{$parts[1]}]":"[{$parts[0]}]";
    }

    /**
     * Escapes a string for safe SQL insertion
     *
     * @param string $value
     * @return string
     */
    private function escapeString($value) {
        $result = '';
        if(gettype($value) == 'string' && strlen($value) == 0) {
            $result = "''";
        }
        else if(in_array(gettype($value), ['integer', 'double'])) {
            $result = $value;
        }
        else if(gettype($value) == 'boolean') {
            $result = ($value)?'1':'0';
        }
        else if(is_null($value)) {
            $result = 'NULL';
        }
        else {
            $value = (string) $value;
            // value is a field name
            if(strlen($value) && substr($value, 0, 1) == '`') {
                $result = self::escapeFieldName($value);
            }
            // value represents NULL SQL value
            else if($value == 'null' || $value == 'NULL') {
                $result = 'NULL';
            }
            // value is any other kind of string
            else {
                if(substr($value, 0, 3) == "h0x") {
                    // hexadecimal string that must be stored as a binary value
                    $result = "'".substr($value, 1)."'";
                }
                else {
                    // regular string that must be escaped
                    $result = "'".str_replace("'", "''", $value)."'";
                }
            }
        }
        return $result;
    }

    /**
     * Gets the SQL WHERE clause
     *
     * @param string $id_field
     * @param array $ids
     * @param array $conditions
     *
     * array( array( array(operand, operator, operand)[, array(operand, operator, operand) [, ...]]) [, array( array(operand, operator, operand)[, array(operand, operator, operand) [, ...]])])
     * array of several series of clauses joined by logical ANDs themselves joined by logical ORs : disjunctions of conjunctions
     * i.e.: (clause[, AND clause [, AND ...]) OR (clause[, AND clause [, AND ...])
     */
    private function getConditionClause($id_field, $ids, $conditions) {
        $sql = '';
        if(empty($conditions)) {
            $conditions = [[[]]];
        }
        for($j = 0, $max_j = count($conditions); $j < $max_j; ++$j) {
            if($j > 0 && strlen($sql) > 0) $sql .= ') OR (';
            if(!empty($ids)) {
                $conditions[$j][] = array($id_field, 'in', $ids);
            }
            for($i = 0, $max_i = count($conditions[$j]); $i < $max_i; ++$i) {
                if($i > 0 && strlen($sql) > 0) {
                    $sql .= ' AND ';
                }
                $cond = $conditions[$j][$i];
                if(!count($cond)) {
                    continue;
                }
                // adjust the field syntax (if necessary)
                $cond[0] = self::escapeFieldName($cond[0]);
                // operator 'in' having a single value as right operand
                if(strcasecmp($cond[1], 'in') == 0 && !is_array($cond[2])) {
                    $cond[2] = array($cond[2]);
                }
                // case-sensitive comparison ('like' operator)
                if(strcasecmp($cond[1], 'like') == 0) {
                    // force mysql to convert field to binary (result will be case-sensitive comparison)
                    $cond[0] = 'BINARY '.$cond[0];
                    $cond[1] = 'LIKE';
                }
                // ilike operator does not exist in MySQL
                if(strcasecmp($cond[1], 'ilike') == 0) {
                    // force mysql to handle the field as a char (necessary for translations that are stored in a binary field)
                    $cond[0] = ' CAST('.$cond[0].' AS CHAR )';
                    $cond[1] = 'LIKE';
                }
                // format the value operand
                if(is_array($cond[2])) {
                    $value = '('.implode(',', array_map( [$this, 'escapeString'], $cond[2] )).')';
                }
                else {
                    $value = $this->escapeString($cond[2]);
                }
                // concatenate query string with current condition
                $sql .= $cond[0].' '.$cond[1].' '.$value;
            }
        }
        if(strlen($sql) > 0) {
            $sql = ' WHERE ('.$sql.')';
        }
        return $sql;
    }

    /**
     * Get records from specified table, according to some conditions.
     *
     * @param	array   $tables       name of involved tables
     * @param	array   $fields       list of requested fields
     * @param	array   $ids          ids to which the selection is limited
     * @param	array   $conditions   list of arrays (field, operand, value)
     * @param	string  $id_field     name of the id field ('id' by default)
     * @param	mixed   $order        string holding name of the order field or maps holding field nmaes as keys and sorting as value
     * @param	integer $start
     * @param	integer $limit
     *
     * @return	resource              reference to query resource
     */
    public function getRecords($tables, $fields=NULL, $ids=NULL, $conditions=NULL, $id_field='id', $order=[], $start=0, $limit=0) {
        // cast tables to an array (passing a single table is accepted)
        if(!is_array($tables)) {
            $tables = (array) $tables;
        }
        // in case fields is not null ans is not an array, cast it to an array (passing a single field is accepted)
        if(isset($fields) && !is_array($fields)) {
            $fields = (array) $fields;
        }
        // in case ids is not null ans is not an array, cast it to an array (passing a single id is accepted)
        if(isset($ids) && !is_array($ids)) {
            $ids = (array) $ids;
        }

        // test values and types
        if(empty($tables)) {
            throw new \Exception(__METHOD__." : unable to build sql query, parameter 'tables' array is empty.", QN_ERROR_SQL);
        }
        /* irrelevant
        if(!empty($fields) && !is_array($fields)) throw new \Exception(__METHOD__." : unable to build sql query, parameter 'fields' is not an array.", QN_ERROR_SQL);
        if(!empty($ids) && !is_array($ids)) throw new \Exception(__METHOD__." : unable to build sql query, parameter 'ids' is not an array.", QN_ERROR_SQL);
        */
        if(!empty($conditions) && !is_array($conditions)) {
            throw new \Exception(__METHOD__." : unable to build sql query, parameter 'conditions' is not an array.", QN_ERROR_SQL);
        }

        // SELECT clause
        // we could add the following directive for better performance (disabled to maximize code portability)
        // $sql = 'SELECT SQL_CALC_FOUND_ROWS ';
        $sql = 'SELECT DISTINCT ';
        if(empty($fields)) {
            $sql .= '*';
        }
        else {
            $selection = [];
            foreach($fields as $field) {
                $selection[] = self::escapeFieldName($field);
            }
            $sql .= implode(',', $selection);
        }

        // FROM clause
        $sql .= ' FROM ';
        foreach($tables as $table_alias => $table_name) {
            if(!is_numeric($table_alias)) {
                $sql .= '`'.$table_name.'` as `'.$table_alias.'`, ';
            }
            else {
                $sql .= '`'.$table_name.'`, ';
            }
        }
        $sql = rtrim($sql, ' ,');

        // WHERE clause
        $sql .= $this->getConditionClause($id_field, $ids, $conditions);

        // order clause
        if(!empty($order)) {
            $order_clause = [];
            if(!is_array($order)) $order = [$order => 'ASC'];
            foreach($order as $field => $sort) {
                $order_clause[] = self::escapeFieldName($field).' '.$sort;
            }
            $sql .= ' ORDER BY '.implode(',', $order_clause);
        }

        // LIMIT clause
        if($limit) {
            $sql .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start, $limit);
        }
        return $this->sendQuery($sql);
    }

    public function setRecords($table, $ids, $fields, $conditions=null, $id_field='id'){
        // test values and types
        if(empty($table)) {
            throw new \Exception(__METHOD__." : unable to build sql query, parameter 'table' empty.", QN_ERROR_SQL);
        }
        if(empty($fields)) {
            throw new \Exception(__METHOD__." : unable to build sql query, parameter 'fields' empty.", QN_ERROR_SQL);
        }

        // UPDATE clause
        $sql = "UPDATE [$table]";

        // SET clause
        $sql .= ' SET ';
        foreach ($fields as $key => $value) {
            $sql .= "$key={$this->escapeString($value)}, ";
        }
        $sql = rtrim($sql, ', ');

        // WHERE clause
        $sql .= $this->getConditionClause($id_field, $ids, $conditions);

        return $this->sendQuery($sql);
    }

    /**
     * Inserts new records in specified table.
     *
     * @param	string $table name of the table in which insert the records
     * @param	array $fields list of involved fields
     * @param	array $values array of arrays specifying the values related to each specified field
     * @return	resource reference to query resource
     */
    public function addRecords($table, $fields, $values) {
        $result = false;
        if (!is_array($fields) || !is_array($values)) {
            throw new \Exception(__METHOD__.' : at least one parameter is missing', QN_ERROR_SQL);
        }
        $cols = '';
        $vals = '';
        foreach ($fields as $field) {
            $cols .= "$field,";
        }
        $cols = rtrim($cols, ',');
        foreach ($values as $val_array) {
            $vals .= '(';
            foreach($val_array as $val) {
                $vals .= $this->escapeString($val).',';
            }
            $vals = rtrim($vals, ',').'),';
        }
        $vals = rtrim($vals, ',');
        if(strlen($cols) > 0 && strlen($vals) > 0) {
            // #todo ignore duplicate enties, if any
            $sql = "INSERT INTO [$table] ($cols) OUTPUT INSERTED.id VALUES $vals;";
            $result = $this->sendQuery($sql);
        }
        return $result;
    }

    public function deleteRecords($table, $ids, $conditions=null, $id_field='id') {
        // DELETE statement
        $sql = 'DELETE FROM ['.$table.']';
        // WHERE clause
        $sql .= $this->getConditionClause($id_field, $ids, $conditions);
        return $this->sendQuery($sql);
    }

}