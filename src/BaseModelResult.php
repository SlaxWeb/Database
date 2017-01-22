<?php
/**
 * Base Model Results trait
 *
 * In use by the Base Model for accessing the Result object.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database;

trait BaseModelResult
{
    /**
     * Database Result Set
     *
     * @var \SlaxWeb\Database\Interfaces\Result
     */
    protected $result = null;

    /**
     * Magic Get Method
     *
     * Check if the Result object has been set and forward the call to the Result
     * object magic get.
     *
     * @param string $name Name of the column
     * @return mixed
     *
     * @exceptions \SlaxWeb\Database\Exception\RowNotFoundException
     *             \SlaxWeb\Database\Exception\ColumnNotFoundException
     */
    public function __get(string $name)
    {
        $this->resultExists();
        return $this->result->{$name};
    }

    /**
     * Next row
     *
     * Check if the Result object has been set and forwards the call to the 'next'
     * method in the Result object.
     *
     * @return bool
     */
    public function next(): bool
    {
        $this->resultExists();
        return $this->result->next();
    }

    /**
     * Previous row
     *
     * Check if the Result object has been set and forwards the call to the 'prev'
     * method in the Result object.
     *
     * @return bool
     */
    public function prev(): bool
    {
        $this->resultExists();
        return $this->result->prev();
    }

    /**
     * Jump to row
     *
     * Check if the Result object has been set and forwards the call to the 'row'
     * method in the Result object.
     *
     * @param int $row Row number
     * @return bool
     */
    public function row(int $row): bool
    {
        $this->resultExists();
        return $this->result->row($row);
    }

    /**
     * Get row count
     *
     * Check if the Result object has been set and forwards the call to the 'rowCount'
     * method in the Result object.
     *
     * @return int
     */
    public function rowCount(): int
    {
        $this->resultExists();
        return $this->result->rowCount();
    }

    /**
     * Get result set
     *
     * Check if the Result object has been set and forwards the call to the 'getResults'
     * method in the Result object.
     *
     * @return array
     */
    public function getResults(): array
    {
        $this->resultExists();
        return $this->result->getResults();
    }

    /**
     * Get Row
     *
     * Check if the Result object has been set and forwards the call to the 'get'
     * method in the Result object.
     *
     * @return \stdClass
     *
     * @exceptions \SlaxWeb\Database\Exception\RowNotFoundException
     */
    public function get(): \stdClass
    {
        $this->resultExists();
        return $this->result->get();
    }

    /**
     * Check result exists
     *
     * Checks if the result has been set appropriately, and throws an exception
     * if this is not the case.
     *
     * @return void
     *
     * @exceptions \SlaxWeb\Database\Exception\NoDataException
     */
    protected function resultExists()
    {
        if ($this->result === null) {
            throw new Exception\NoDataException(
                "Result set is empty, can not proceed."
            );
        }
    }
}
