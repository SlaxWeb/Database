<?php
namespace SlaxWeb\Database\Exception;

/**
 * No Join Condition Exception
 *
 * Thrown when a join is added but a condition for it does not exist.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class NoJoinConditionException extends \Exception
{
}
