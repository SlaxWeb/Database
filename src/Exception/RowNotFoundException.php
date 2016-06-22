<?php
/**
 * Row Not Found Error Exception
 *
 * Thrown when a request to retrieve an non-existant row to the Result object is
 * made.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Exception;

class RowNotFoundErrorException extends \Exception
{
}
