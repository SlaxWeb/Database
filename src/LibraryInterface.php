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
namespace SlaxWeb\Database;

use SlaxWeb\Database\Error;
use SlaxWeb\Database\Exception\NoErrorException;

interface LibraryInterface
{
    /**
     * Insert row
     *
     * Inserts a row into the database with the provided data. Returns bool(true)
     * on success and bool(false) on failure.
     *
     * @param array $data Data to be inserted
     * @return bool
     */
    public function insert(array $data): bool;

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
