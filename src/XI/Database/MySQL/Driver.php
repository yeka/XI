<?php

namespace XI\Database\MySQL;

use XI\Database\DriverInterface;

class Driver implements DriverInterface
{
    /** @var \XI\Database\Driver */
    protected $parent;

    public function setParent(\XI\Database\Driver $parent)
    {
        $this->parent = $parent;
    }

    var $dbdriver = 'mysql';

    // The character used for escaping
    var $_escape_char = '`';

    // clause and character used for LIKE escape sequences - not used in MySQL
    var $_like_escape_str = '';
    var $_like_escape_chr = '';

    /**
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     */
    var $delete_hack = TRUE;

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = 'SELECT COUNT(*) AS ';
    var $_random_keyword = ' RAND()'; // database specific random keyword

    // whether SET NAMES must be used to set the character set
    var $use_set_names;

    /**
     * Non-persistent database connection
     *
     * @access    private called by the base class
     * @return    resource
     */
    public function connect()
    {
        if ($this->parent->port != '') {
            $this->parent->hostname .= ':' . $this->parent->port;
        }

        return @mysql_connect($this->parent->hostname, $this->parent->username, $this->parent->password, TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Persistent database connection
     *
     * @access    private called by the base class
     * @return    resource
     */
    public function persistentConnect()
    {
        if ($this->parent->port != '') {
            $this->parent->hostname .= ':' . $this->parent->port;
        }

        return @mysql_pconnect($this->parent->hostname, $this->parent->username, $this->parent->password);
    }

    // --------------------------------------------------------------------

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     *
     * @access    public
     * @return    void
     */
    public function reConnect()
    {
        if (mysql_ping($this->parent->conn_id) === FALSE) {
            $this->parent->conn_id = FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Select the database
     *
     * @access    private called by the base class
     * @return    resource
     */
    public function selectDatabase()
    {
        return @mysql_select_db($this->parent->database, $this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * Set client character set
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    resource
     */
    public function setCharset($charset, $collation)
    {
        if (!isset($this->use_set_names)) {
            // mysql_set_charset() requires PHP >= 5.2.3 and MySQL >= 5.0.7, use SET NAMES as fallback
            $this->use_set_names = (version_compare(PHP_VERSION, '5.2.3', '>=') && version_compare(mysql_get_server_info(), '5.0.7', '>=')) ? FALSE : TRUE;
        }

        if ($this->use_set_names === TRUE) {
            return @mysql_query("SET NAMES '" . $this->escape_str($charset) . "' COLLATE '" . $this->escape_str($collation) . "'", $this->parent->conn_id);
        } else {
            return @mysql_set_charset($charset, $this->parent->conn_id);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Version number query string
     *
     * @access    public
     * @return    string
     */
    public function version()
    {
        return "SELECT version() AS ver";
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query
     *
     * @access    private called by the base class
     * @param    string    an SQL query
     * @return    resource
     */
    public function execute($sql)
    {
        $sql = $this->prepareQuery($sql);
        return @mysql_query($sql, $this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     *
     * @access    private called by execute()
     * @param    string    an SQL query
     * @return    string
     */
    public function prepareQuery($sql)
    {
        // "DELETE FROM TABLE" returns 0 affected rows This hack modifies
        // the query so that it returns the number of affected rows
        if ($this->delete_hack === TRUE) {
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
                $sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
            }
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Begin Transaction
     *
     * @access    public
     * @return    bool
     */
    public function transactionBegin($test_mode = FALSE)
    {
        if (!$this->trans_enabled) {
            return TRUE;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return TRUE;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->_trans_failure = ($test_mode === TRUE) ? TRUE : FALSE;

        $this->parent->simpleQuery('SET AUTOCOMMIT=0');
        $this->parent->simpleQuery('START TRANSACTION'); // can also be BEGIN or BEGIN WORK
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Commit Transaction
     *
     * @access    public
     * @return    bool
     */
    public function transactionCommit()
    {
        if (!$this->trans_enabled) {
            return TRUE;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return TRUE;
        }

        $this->parent->simpleQuery('COMMIT');
        $this->parent->simpleQuery('SET AUTOCOMMIT=1');
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Rollback Transaction
     *
     * @access    public
     * @return    bool
     */
    public function transactionRollback()
    {
        if (!$this->trans_enabled) {
            return TRUE;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return TRUE;
        }

        $this->parent->simpleQuery('ROLLBACK');
        $this->parent->simpleQuery('SET AUTOCOMMIT=1');
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Escape String
     *
     * @access    public
     * @param    string
     * @param    bool    whether or not the string will be used in a LIKE condition
     * @return    string
     */
    public function escape($str, $like = FALSE)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        if (function_exists('mysql_real_escape_string') AND is_resource($this->parent->conn_id)) {
            $str = mysql_real_escape_string($str, $this->parent->conn_id);
        } elseif (function_exists('mysql_escape_string')) {
            $str = mysql_real_escape_string($str);
        } else {
            $str = addslashes($str);
        }

        // escape LIKE condition wildcards
        if ($like === TRUE) {
            $str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Affected Rows
     *
     * @access    public
     * @return    integer
     */
    public function affectedRows()
    {
        return @mysql_affected_rows($this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * Insert ID
     *
     * @access    public
     * @return    integer
     */
    public function insertId()
    {
        return @mysql_insert_id($this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * "Count All" query
     *
     * Generates a platform-specific query string that counts all records in
     * the specified database
     *
     * @access    public
     * @param    string
     * @return    string
     */
    public function countAll($table = '')
    {
        if ($table == '') {
            return 0;
        }

        $query = $this->query($this->_count_string . $this->_protect_identifiers('numrows') . " FROM " . $this->_protect_identifiers($table, TRUE, NULL, FALSE));

        if ($query->num_rows() == 0) {
            return 0;
        }

        $row = $query->row();
        $this->_reset_select();
        return (int)$row->numrows;
    }

    // --------------------------------------------------------------------

    /**
     * List table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @access    private
     * @param    boolean
     * @return    string
     */
    public function listTables($prefix_limit = FALSE)
    {
        $sql = "SHOW TABLES FROM " . $this->_escape_char . $this->database . $this->_escape_char;

        if ($prefix_limit !== FALSE AND $this->dbprefix != '') {
            $sql .= " LIKE '" . $this->parent->escapeLikeStr($this->dbprefix) . "%'";
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @access    public
     * @param    string    the table name
     * @return    string
     */
    public function listColumns($table = '')
    {
        return "SHOW COLUMNS FROM " . $this->parent->_protect_identifiers($table, TRUE, NULL, FALSE);
    }

    // --------------------------------------------------------------------

    /**
     * Field data query
     *
     * Generates a platform-specific query so that the column data can be retrieved
     *
     * @access    public
     * @param    string    the table name
     * @return    object
     */
    public function fieldData($table)
    {
        return "DESCRIBE " . $table;
    }

    // --------------------------------------------------------------------

    /**
     * The error message string
     *
     * @access    private
     * @return    string
     */
    public function errorMessage()
    {
        return mysql_error($this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * The error message number
     *
     * @access    private
     * @return    integer
     */
    public function errorNumber()
    {
        return mysql_errno($this->parent->conn_id);
    }

    // --------------------------------------------------------------------

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @access    private
     * @param    string
     * @return    string
     */
    public function escapeIdentifiers($item)
    {
        if ($this->_escape_char == '') {
            return $item;
        }

        foreach ($this->parent->_reserved_identifiers as $id) {
            if (strpos($item, '.' . $id) !== FALSE) {
                $str = $this->_escape_char . str_replace('.', $this->_escape_char . '.', $item);

                // remove duplicates if the user already included the escape
                return preg_replace('/[' . $this->_escape_char . ']+/', $this->_escape_char, $str);
            }
        }

        if (strpos($item, '.') !== FALSE) {
            $str = $this->_escape_char . str_replace('.', $this->_escape_char . '.' . $this->_escape_char, $item) . $this->_escape_char;
        } else {
            $str = $this->_escape_char . $item . $this->_escape_char;
        }

        // remove duplicates if the user already included the escape
        return preg_replace('/[' . $this->_escape_char . ']+/', $this->_escape_char, $str);
    }

    // --------------------------------------------------------------------

    /**
     * From Tables
     *
     * This function implicitly groups FROM tables so there is no confusion
     * about operator precedence in harmony with SQL standards
     *
     * @access    public
     * @param    type
     * @return    type
     */
    public function fromTables($tables)
    {
        if (!is_array($tables)) {
            $tables = array($tables);
        }

        return '(' . implode(', ', $tables) . ')';
    }

    // --------------------------------------------------------------------

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    public function insert($table, $keys, $values)
    {
        return "INSERT INTO " . $table . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
    }

    // --------------------------------------------------------------------


    /**
     * Replace statement
     *
     * Generates a platform-specific replace string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    public function replace($table, $keys, $values)
    {
        return "REPLACE INTO " . $table . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
    }

    // --------------------------------------------------------------------

    /**
     * Insert_batch statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    public function insertBatch($table, $keys, $values)
    {
        return "INSERT INTO " . $table . " (" . implode(', ', $keys) . ") VALUES " . implode(', ', $values);
    }

    // --------------------------------------------------------------------


    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the update data
     * @param    array    the where clause
     * @param    array    the orderby clause
     * @param    array    the limit clause
     * @return    string
     */
    public function update($table, $values, $where, $orderby = array(), $limit = FALSE)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key . ' = ' . $val;
        }

        $limit = (!$limit) ? '' : ' LIMIT ' . $limit;

        $orderby = (count($orderby) >= 1) ? ' ORDER BY ' . implode(", ", $orderby) : '';

        $sql = "UPDATE " . $table . " SET " . implode(', ', $valstr);

        $sql .= ($where != '' AND count($where) >= 1) ? " WHERE " . implode(" ", $where) : '';

        $sql .= $orderby . $limit;

        return $sql;
    }

    // --------------------------------------------------------------------


    /**
     * Update_Batch statement
     *
     * Generates a platform-specific batch update string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the update data
     * @param    array    the where clause
     * @return    string
     */
    public function updateBatch($table, $values, $index, $where = NULL)
    {
        $ids = array();
        $where = ($where != '' AND count($where) >= 1) ? implode(" ", $where) . ' AND ' : '';

        foreach ($values as $key => $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                if ($field != $index) {
                    $final[$field][] = 'WHEN ' . $index . ' = ' . $val[$index] . ' THEN ' . $val[$field];
                }
            }
        }

        $sql = "UPDATE " . $table . " SET ";
        $cases = '';

        foreach ($final as $k => $v) {
            $cases .= $k . ' = CASE ' . "\n";
            foreach ($v as $row) {
                $cases .= $row . "\n";
            }

            $cases .= 'ELSE ' . $k . ' END, ';
        }

        $sql .= substr($cases, 0, -2);

        $sql .= ' WHERE ' . $where . $index . ' IN (' . implode(',', $ids) . ')';

        return $sql;
    }

    // --------------------------------------------------------------------


    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @access    public
     * @param    string    the table name
     * @return    string
     */
    public function truncate($table)
    {
        return "TRUNCATE " . $table;
    }

    // --------------------------------------------------------------------

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the where clause
     * @param    string    the limit clause
     * @return    string
     */
    public function delete($table, $where = array(), $like = array(), $limit = FALSE)
    {
        $conditions = '';

        if (count($where) > 0 OR count($like) > 0) {
            $conditions = "\nWHERE ";
            $conditions .= implode("\n", $this->ar_where);

            if (count($where) > 0 && count($like) > 0) {
                $conditions .= " AND ";
            }
            $conditions .= implode("\n", $like);
        }

        $limit = (!$limit) ? '' : ' LIMIT ' . $limit;

        return "DELETE FROM " . $table . $conditions . $limit;
    }

    // --------------------------------------------------------------------

    /**
     * Limit string
     *
     * Generates a platform-specific LIMIT clause
     *
     * @access    public
     * @param    string    the sql query string
     * @param    integer    the number of rows to limit the query to
     * @param    integer    the offset value
     * @return    string
     */
    public function limit($sql, $limit, $offset)
    {
        if ($offset == 0) {
            $offset = '';
        } else {
            $offset .= ", ";
        }

        return $sql . "LIMIT " . $offset . $limit;
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection
     *
     * @access    public
     * @param    resource
     * @return    void
     */
    public function close($conn_id)
    {
        @mysql_close($conn_id);
    }

}
