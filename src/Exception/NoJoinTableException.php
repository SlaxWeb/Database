<?php
namespace SlaxWeb\Database\Exception;

/**
 * No Join Table Exception
 *
 * No table to join with was added to the builder, but an attempt to add join conditions
 * was made, which is a non-recoverable error, and this exception is thrown.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class NoJoinTableException extends \Exception
{
}
