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
use SlaxWeb\Database\Exception\NoErrorException;
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
     * successful. Throw an exception on error. The input array defines a list of
     * columns that need to get selected from the database. If a SQL function should
     * be executed on a column, like i.e., MAX, then the key of that value should
     * be the name of that function. If the key is numeric, the column is used normally
     * in the query.
     *
     * @param string $table Table on which the select statement is to be executed
     * @param array $cols Array of columns for the SELECT statement
     * @return \SlaxWeb\DatabasePDO\Result
     *
     * @exceptions \SlaxWeb\DatabasePDO\Exception\QueryException
     *             \SlaxWeb\DatabasePDO\Exception\NoDataException
     */
    public function select(string $table, array $cols): ResultInterface;

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
