<?php
/**
 * Database Library Interface
 *
 * Provides method signatures that a SlaxWeb Framework Database Library must implement
 * in order to be considered a usable Database Library.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Interfaces;

use SlaxWeb\Database\Error;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

interface Library
{
    /**
     * Available database drivers
     */
    const DB_CUBRID = "cubrid";
    const DB_DBLIB = "dblib";
    const DB_FIREBIRD = "firebird";
    const DB_IBM = "ibm";
    const DB_INFORMIX = "informix";
    const DB_MYSQL = "mysql";
    const DB_OCI = "oci";
    const DB_ODBC = "odbc";
    const DB_PGSQL = "pgsql";
    const DB_SQLITE = "sqlite";
    const DB_SQLSRV = "sqlsrv";
    const DB_4D = "4d";

    /**
     * Execute Query
     *
     * Executes the received query and binds the received parameters into the query
     * to decrease the chance of an SQL injection. Returns bool(true) if query was
     * successfuly executed, and bool(false) if it was not. If the query yielded
     * a result set, a Result object will be populated.
     *
     * @param string $query The Query to be executed
     * @param array $data Data to be bound into the Query
     * @return bool
     */
    public function execute(string $query, array $data): bool;

    /**
     * Insert row
     *
     * Inserts a row into the database with the provided data. Returns bool(true)
     * on success and bool(false) on failure.
     *
     * @param string $table Table to which the data is to be inserted
     * @param array $data Data to be inserted
     * @return bool
     */
    public function insert(string $table, array $data): bool;

    /**
     * Select query
     *
     * Run a select query against the database and return the result set if it was
     * successful. On error it returns an empty result set. The input array defines
     * a list of columns that need to get selected from the database. If the array
     * item is another array, it needs to hold the "func" and "col" keys at least,
     * defining the SQL DML function, as well as the column name. A third item with
     * the key name "as" can be added, and this name will be used in the "AS" statement
     * in the SQL DML for that column.
     *
     * @param string $table Table on which the select statement is to be executed
     * @param array $cols Array of columns for the SELECT statement
     * @return \SlaxWeb\DatabasePDO\Result
     */
    public function select(string $table, array $cols): ResultInterface;

    /**
     * Update query
     *
     * Run an update query against the database. The input array defines a list
     * of columns and their new values that they should be updated to. The where
     * predicates that are set before the call to this * method will be used in
     * the update statement. Returns bool(true) on success, and bool(false) on error.
     *
     * @param string $table Table on which the update statement is to be executed
     * @param array $cols Column list with values
     * @return bool
     */
    public function update(string $table, array $cols): bool;

    /**
     * Delete query
     *
     * Run an delete query against the database. Returns bool(true) on success,
     * and bool(false) on error.
     *
     * @param string $table Table on which the delete statement is to be executed
     * @return bool
     */
    public function delete(string $table): bool;

    /**
     * Fetch Results
     *
     * It fetches the results from the last executed statement, creates the Result
     * object and returns it.
     *
     * @return \SlaxWeb\Database\ResultInterface
     */
    public function fetch(): ResultInterface;

    /**
     * Add Where Predicate
     *
     * Adds a SQL DML WHERE predicate to the query.
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $lOpr Logical operator, default string("=")
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return void
     */
    public function where(string $column, $value, string $lOpr = "=", string $cOpr = "AND");

    /**
     * Add Where Predicate Group
     *
     * Adds a group of predicates to the list. The closure received as input must
     * receive the builder instance for building groups.
     *
     * @param Closure $predicates Grouped predicates definition closure
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return void
     */
    public function groupWhere(\Closure $predicates, string $cOpr = "AND");

    /**
     * Where Nested Select
     *
     * Add a nested select as a value to the where predicate.
     *
     * @param string $column Column name
     * @param closure $nested Nested builder
     * @param string $lOpr Logical operator, default string("IN")
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return void
     */
    public function nestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = "IN",
        string $cOpr = "AND"
    );

    /**
     * Add table to join
     *
     * Adds a new table to join with the main table to the list of joins. If only
     * a table is added without a condition with the 'joinCond', an exception will
     * be thrown when an attempt to create a query is made.
     *
     * @param string $table Table to join to
     * @param string $type Join type, default string("INNER JOIN")
     * @return void
     */
    public function join(string $table, string $type = "INNER JOIN");

    /**
     * Add join condition
     *
     * Adds a JOIN condition to the last join added. If no join was yet added, an
     * exception is raised.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @return void
     */
    public function joinCond(string $primKey, string $forKey, string $cOpr = "=");

    /**
     * Add OR join condition
     *
     * Alias for the 'joinCond' with the "OR" logical operator.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @return void
     */
    public function orJoinCond(string $primKey, string $forKey, string $cOpr = "=");

    /**
     * Join Columns
     *
     * Add columns to include in the select column list. If no table for joining
     * was yet added, an exception is raised. Same rules apply to the column list
     * as in the 'select' method.
     *
     * @param array $cols Column list
     * @return void
     */
    public function joinCols(array $cols);

    /**
     * Group by
     *
     * Add a column to the group by list.
     *
     * @param string $col Column name to be added to the group by list.
     * @return void
     */
    public function groupBy(string $col);

    /**
     * Order by
     *
     * Add a column to the order by list.
     *
     * @param string $col Column name to be added to the group by list
     * @param string $direction Direction of order, default self::ORDER_ASC
     * @param string $func SQL function to use ontop of the column, default string("")
     * @return void
     */
    public function orderBy(string $col, string $direction = "ASC", string $func = "");

    /**
     * Limit
     *
     * Limit number of rows to be returned from the database. Second parameter will
     * also add an offset to the statement.
     *
     * @param int $limit Number of rows to limit the result set to
     * @param int $offset Number of rows for the result to be offset from, default int(0)
     * @return void
     */
    public function limit(int $limit, int $offset = 0);

    /**
     * Get last error
     *
     * Retrieves the error of the last executed query. If there was no error, an
     * exception must be thrown.
     *
     * @return \SlaxWeb\Database\Error
     *
     * @exceptions \SlaxWeb\Database\Exception\NoErrorException
     */
    public function lastError(): Error;
}
