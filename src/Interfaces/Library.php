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
    public function execute(string $query, array $data = []): bool;

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
