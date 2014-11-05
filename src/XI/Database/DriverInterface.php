<?php

namespace XI\Database;

interface DriverInterface
{

    public function connect();

    public function persistentConnect();

    public function reConnect();

    public function selectDatabase();

    public function setCharset($charset, $collation);

    public function version();

    public function execute($sql);

    public function prepareQuery($sql);

    public function transactionBegin($test_mode = FALSE);

    public function transactionCommit();

    public function transactionRollback();

    public function escape($str, $like = FALSE);

    public function affectedRows();

    public function insertId();

    public function countAll($table = '');

    public function listTables($prefix_limit = FALSE);

    public function listColumns($table = '');

    public function fieldData($table);

    public function errorMessage();

    public function errorNumber();

    public function escapeIdentifiers($item);

    public function fromTables($tables);

    public function insert($table, $keys, $values);

    public function replace($table, $keys, $values);

    public function insertBatch($table, $keys, $values);

    public function update($table, $values, $where, $orderby = array(), $limit = FALSE);

    public function updateBatch($table, $values, $index, $where = NULL);

    public function truncate($table);

    public function delete($table, $where = array(), $like = array(), $limit = FALSE);

    public function limit($sql, $limit, $offset);

    public function close($conn_id);

    public function setParent(Driver $parent);
}

