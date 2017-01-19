<?php
/**
 * No Data Exception
 *
 * Thrown when a request to retrieve data from the Base Model is made, yet no data
 * was retrieved from the database before.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Exception;

class NoDataException extends \Exception
{
}
