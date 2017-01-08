<?php
/**
 * Database Result Interface
 *
 * The result interface provides the method signatures for each database library
 * result class. Every result class muss implement this interface and provide the
 * required functionality.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Interfaces;

interface Result
{
    /**
     * Magic Get Method
     *
     * Retrieves the result row column value and returns it to the caller. If the
     * internal index is not pointing to a valid row, or the requested column is
     * not part of the result set, an exception is thrown.
     *
     * @param string $name Name of the column
     * @return mixed
     *
     * @exceptions \SlaxWeb\Database\Exception\ResultRowNotFoundException
     *             \SlaxWeb\Database\Exception\ColumnNotFoundException
     */
    public function __get(string $name);

    /**
     * Next row
     *
     * Move the internal pointer to the next row of the result array. If there is
     * no row found under the next index, bool(false) is returned, otherwise bool(true)
     * is returned.
     *
     * @return bool
     */
    public function next(): bool;

    /**
     * Previous row
     *
     * Move the internal pointer to the previous row of the result array. If there
     * is no row found under the previous index, bool(false) is returned, otherwise
     * bool(true) is returned.
     *
     * @return bool
     */
    public function prev(): bool;

    /**
     * Jump to row
     *
     * Move the internal pointer to the passed in row of the result array. If there
     * is no row found under the passed in row, bool(false) is returned, otherwise
     * bool(true) is returned.
     *
     * @param int $row Row number
     * @return bool
     */
    public function row(int $row): bool;

    /**
     * Get row count
     *
     * Get the row count of the result set.
     *
     * @return int
     */
    public function rowCount(): int;

    /**
     * Get result set
     *
     * Returns the raw result set array to the caller.
     *
     * @return array
     */
    public function getResults(): array;

    /**
     * Get Row
     *
     * Returns the row object to the caller. If the row does not exists, an exception
     * is thrown.
     *
     * @return \stdClass
     *
     * @exceptions \SlaxWeb\Database\Exception\ResultRowNotFoundException
     */
    public function get(): \stdClass;
}
