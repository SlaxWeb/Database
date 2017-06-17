<?php
namespace SlaxWeb\Database\Test\Unit;

use SlaxWeb\Database\Query\Where\Group;
use SlaxWeb\Database\Query\Where\Predicate;

/**
 * Where Predicate Group Tests
 *
 * Test the predicate group class that it constructs the WHERE SQL DML list correctly.
 * This test is a bit hacky, because the Group class instantiates Predicate classes,
 * a failed test here might mean that the Predicate is actually failing, and not
 * Group class!
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class GroupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test convert
     *
     * Ensure that predicates are properly converted to string.
     *
     * @return void
     */
    public function testConvert()
    {
        $group = new Group;
        $group->setDelim("\"\"");
        $group->table("\"foos\"");
        $group->where("foo", "bar");
        $group->where("baz", "qux");

        $this->assertEquals(" AND (\"foos\".\"foo\" = ? AND \"foos\".\"baz\" = ?)", $group->convert());

        $group = new Group("OR");
        $group->setDelim("\"\"");
        $group->table("\"foos\"");
        $group->where("foo", "bar");
        $group->where("baz", "qux");

        $this->assertEquals(" OR (\"foos\".\"foo\" = ? AND \"foos\".\"baz\" = ?)", $group->convert());
    }
}
