<?php
/**
 * No Primary Key Exception
 *
 * Thrown when model joining is attempted and the joining model does not have a
 * primary key set.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Exception;

class NoPrimKeyException extends \Exception
{
}
