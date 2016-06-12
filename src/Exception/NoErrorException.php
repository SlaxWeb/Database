<?php
/**
 * No Error Exception
 *
 * Thrown when no query raised an exception yet an attempt to retrieve an error
 * was made.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Exception;

class NoErrorException extends \Exception
{
}
