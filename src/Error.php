<?php
/**
 * Database Error
 *
 * Holds the information about a database error that occured while executing a query.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database;

class Error
{
    use \SlaxWeb\GetSet\MagicGet;

    /**
     * Error message
     *
     * @var string
     */
    protected $_message = "";

    /**
     * Query
     *
     * @var string
     */
    protected $_query = "";

    /**
     * Class constructor
     *
     * Sets the error data to protected properties.
     *
     * @param string $message Error message
     * @param string $query Query at which the error occured
     */
    public function __construct(string $message, string $query)
    {
        $this->_message = $message;
        $this->_query = $query;
    }
}
