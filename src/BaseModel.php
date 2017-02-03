<?php
/**
 * Base Model
 *
 * Base Model which all Model classes should extend from. The Base Model provides
 * functionality for execution of queries against a database with the help of the
 * database library which provides a connection to a specific RDBS, and also provides
 * basic query building methods.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database;

use ICanBoogie\Inflector;
use SlaxWeb\Database\Error;
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Config\Container as Config;
use SlaxWeb\Database\Exception\QueryException;
use SlaxWeb\Database\Interfaces\Library as Database;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

abstract class BaseModel
{
    use BaseModelResult;

    /**
     * Table name style
     */
    const TBL_NAME_CAMEL_UCFIRST = 1;
    const TBL_NAME_CAMEL_LCFIRST = 2;
    const TBL_NAME_UNDERSCORE = 3;
    const TBL_NAME_UPPERCASE = 4;
    const TBL_NAME_LOWERCASE = 5;

    /**
     * Callback invokation type
     */
    const CALLBACK_BEFORE = true;
    const CALLBACK_AFTER = false;

    /**
     * Join types
     */
    const JOIN_INNER = "INNER JOIN";
    const JOIN_LEFT = "LEFT OUTER JOIN";
    const JOIN_RIGHT = "RIGHT OUTER JOIN";
    const JOIN_FULL = "FULL OUTER JOIN";
    const JOIN_CROSS = "CROSS JOIN";

    /**
     * Soft delete options
     */
    const SDEL_VAL_BOOL = 1;
    const SDEL_VAL_TIMESTAMP = 2;

    /**
     * Table name
     *
     * @var string
     */
    public $table = "";

    /**
     * Logger object
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * Config object
     *
     * @var \SlaxWeb\Config\Container
     */
    protected $config = null;

    /**
     * Inflector object
     *
     * @var \ICanBoogie\Inflector
     */
    protected $inflector = null;

    /**
     * Database Library
     *
     * @var \SlaxWeb\Database\Interfaces\Library
     */
    protected $db = null;

    /**
     * Last Query Error object
     *
     * @var \SlaxWeb\Database\Error
     */
    protected $error = null;

    /**
     * Soft delete
     *
     * @var bool
     */
    protected $softDelete = false;

    /**
     * Soft delete column
     *
     * @var string
     */
    protected $delCol = "";

    /**
     * Soft delete value type
     *
     * @var int
     */
    protected $delValType = 0;

    /**
     * Callback definitions
     *
     * List of callbacks:
     * - beforeInit
     * - afterInit
     * - beforeCreate
     * - afterCreate
     * - beforeRead
     * - afterRead
     * - beforeUpdate
     * - afterUpdate
     * - beforeDelete
     * - afterDelete
     *
     * @var array<callable>
     */
    protected $beforeInit = [];
    protected $afterInit = [];
    protected $beforeCreate = [];
    protected $afterCreate = [];
    protected $beforeRead = [];
    protected $afterRead = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Class constructor
     *
     * Initialize the Base Model, by storging injected dependencies into class properties.
     *
     * @param \Psr\Log\LoggerInterface $logger PSR-7 compliant logger object
     * @param \SlaxWeb\Config\Container $config Configuration container object
     * @param \ICanBoogie\Inflector $inflector Inflector object for pluralization and word transformations
     * @param \SlaxWeb\Database\Interface\Library $db Database library object
     * @return void
     */
    public function __construct(Logger $logger, Config $config, Inflector $inflector, Database $db)
    {
        $this->invokeCallback("init");

        $this->logger = $logger;
        $this->config = $config;
        $this->inflector = $inflector;
        $this->db = $db;

        if ($this->table === "" && $this->config["database.autoTable"]) {
            $this->setTable();
        }
        $this->setSoftDelete();

        $this->logger->info("Model initialized successfuly", ["model" => get_class($this)]);

        $this->invokeCallback("init", self::CALLBACK_AFTER);
    }

    /**
     * Create record
     *
     * Creates a record row in the database with the supplied data and the help
     * of the database library. It returns bool(true) on success, and bool(false)
     * on failure.
     *
     * @param array $data Data for the create statement
     * @return bool
     */
    public function create(array $data): bool
    {
        $this->invokeCallback("create");

        if (($status = $this->db->insert($this->table, $data)) === false) {
            $this->error = $this->db->lastError();
        }

        $this->invokeCallback("create", self::CALLBACK_AFTER);
        return $status;
    }

    /**
     * Select query
     *
     * Run a select query on the database with the previously assigned columns,
     * joins, group bys, limits, etc. The column list is an array, if the key of
     * an entry is of type string, then the name of that key is used as a SQL function.
     * On success it returns the Result object, and on error it raises an Exception.
     *
     * @param array $columns Column list
     * @return \SlaxWeb\Database\Interfaces\Result
     *
     * @exceptions \SlaxWeb\Database\Exception\QueryException
     *             \SlaxWeb\Database\Exception\NoDataException
     */
    public function select(array $columns): ResultInterface
    {
        $this->invokeCallback("read");

        $this->result = $this->db->select($this->table, $columns);

        $this->invokeCallback("read", self::CALLBACK_AFTER);
        return $this->result;
    }

    /**
     * Update query
     *
     * Run an update query against the database. The input array defines a list
     * of columns and their new values that they should be updated to. The where
     * predicates that are set before the call to this * method will be used in
     * the update statement. Returns bool(true) on success, and bool(false) on error.
     *
     * @param array $columns Column list with values
     * @return bool
     */
    public function update(array $columns): bool
    {
        $this->invokeCallback("update");

        if (($status = $this->db->update($this->table, $columns)) === false) {
            $this->error = $this->db->lastError();
        }

        $this->invokeCallback("update", self::CALLBACK_AFTER);
        return $status;
    }

    /**
     * Delete query
     *
     * Run an delete query against the database. Returns bool(true) on success,
     * and bool(false) on error.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $this->invokeCallback("delete");

        if ($this->softDelete) {
            $val = $this->delValType === self::SDEL_VAL_TIMESTAMP
                ? ["func" => "NOW()"]
                : true;
            $status = $this->update([$this->delCol => $val]);
        } elseif (($status = $this->db->delete($this->table)) === false) {
            $this->error = $this->db->lastError();
        }

        $this->invokeCallback("delete", self::CALLBACK_AFTER);
        return $status;
    }

    /**
     * Where predicate
     *
     * Adds a where predicate for the next query to be ran. The method takes 3 input
     * arguments, where the first is the name of column, the second is the value
     * of the predicate, and the 3rd is an logical operator linking the two. The
     * logical operator defaults to the equals signs(=).
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $opr Logical operator
     * @return self
     */
    public function where(string $column, $value, string $opr = "="): self
    {
        $this->db->where($column, $value, $opr);
        return $this;
    }

    /**
     * Or Where predicate
     *
     * Works the same way as 'Where predicate' method, except it adds the predicate
     * to the list with the "OR" comparison operator.
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $opr Logical operator
     * @return self
     */
    public function orWhere(string $column, $value, string $opr = "="): self
    {
        $this->db->where($column, $value, $opr, "OR");
        return $this;
    }

    /**
     * Grouped Where predicates
     *
     * Adds a group of predicates to the the predicate list. The method must receive
     * a closure as its input parameter. The closure in turn receives the builder
     * object as its input parameter. Additional where predicates must be added
     * to the builder through this object.
     *
     * @param Closure $predicates Grouped predicates definition closure
     * @return self
     */
    public function groupWhere(\Closure $predicates): self
    {
        $this->db->groupWhere($predicates);
        return $this;
    }

    /**
     * Or Grouped Where predicates
     *
     * Works the same way as 'Grouped Where predicates' method, except it adds the
     * predicate group to the list with the "OR" comparison operator.
     *
     * @param Closure $predicates Grouped predicates definition closure
     * @return self
     */
    public function orGroupWhere(\Closure $predicates): self
    {
        $this->db->groupWhere($predicates, "OR");
        return $this;
    }

    /**
     * Where Nested Select
     *
     * Add a nested select as a value to the where predicate.
     *
     * @param string $column Column name
     * @param closure $nested Nested builder
     * @param string $lOpr Logical operator, default string("IN")
     * @return self
     */
    public function nestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = "IN"
    ): self {
        $this->db->nestedWhere($column, $nested, $lOpr);
        return $this;
    }

    /**
     * Or Where Nested Select
     *
     * Works the same way as "Where Nested Select" except that it links the nested
     * select predicate with an "OR" comparisson operator instead of an "AND".
     *
     * @param string $column Column name
     * @param closure $nested Nested builder
     * @param string $lOpr Logical operator, default string("IN")
     * @return self
     */
    public function orNestedWhere(
        string $column,
        \Closure $nested,
        string $lOpr = "IN"
    ): self {
        $this->db->nestedWhere($column, $nested, $lOpr, "OR");
        return $this;
    }

    /**
     * Add table to join
     *
     * Adds a new table to join with the main table to the list of joins. If only
     * a table is added without a condition with the 'joinCond', an exception will
     * be thrown when an attempt to create a query is made.
     *
     * @param string $table Table name to which the join is to be made
     * @param string $type Join type, default string("INNER JOIN")
     * @return self
     */
    public function join(string $table, string $type = "INNER JOIN"): self
    {
        $this->db->join($table, $type);
        return $this;
    }

    /**
     * Add join condition
     *
     * Adds a JOIN condition to the last join added. If no join was yet added, an
     * exception is raised.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @param string $lOpr Logical operator for multiple JOIN conditions
     * @return self
     */
    public function joinCond(string $primKey, string $forKey, string $cOpr = "="): self
    {
        $this->db->joinCond($primKey, $forKey, $cOpr);
        return $this;
    }

    /**
     * Add OR join condition
     *
     * Alias for the 'joinCond' with the "OR" logical operator.
     *
     * @param string $primKey Key of the main table for the condition
     * @param string $forKey Key of the joining table
     * @param string $cOpr Comparison operator for the two keys
     * @param string $lOpr Logical operator for multiple JOIN conditions
     * @return self
     */
    public function orJoinCond(string $primKey, string $forKey, string $cOpr = "="): self
    {
        $this->db->joinCond($primKey, $forKey, $cOpr, "OR");
        return $this;
    }

    /**
     * Join Columns
     *
     * Add columns to include in the select column list. If no table for joining
     * was yet added, an exception is raised. Same rules apply to the column list
     * as in the 'select' method.
     *
     * @param array $cols Column list
     * @return self
     */
    public function joinCols(array $cols): self
    {
        $this->db->joinCols($cols);
        return $this;
    }

    /**
     * Group by
     *
     * Add a column to the group by list.
     *
     * @param string $col Column name to be added to the group by list.
     * @return self
     */
    public function groupBy(string $col): self
    {
        $this->db->groupBy($col);
        return $this;
    }

    /**
     * Order by
     *
     * Add a column to the order by list.
     *
     * @param string $col Column name to be added to the group by list
     * @param string $direction Direction of order, default self::ORDER_ASC
     * @param string $func SQL function to use ontop of the column, default string("")
     * @return self
     */
    public function orderBy(string $col, string $direction = "ASC", string $func = ""): self
    {
        $this->db->orderBy($col, $direction, $func);
        return $this;
    }

    /**
     * Limit
     *
     * Limit number of rows to be returned from the database. Second parameter will
     * also add an offset to the statement.
     *
     * @param int $limit Number of rows to limit the result set to
     * @param int $offset Number of rows for the result to be offset from, default int(0)
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->db->limit($limit, $offset);
        return $this;
    }

    /**
     * Get last error
     *
     * Retrieves the last occured error from the database library and returns it.
     *
     * @return \SlaxWeb\Database\Error
     */
    public function lastError(): Error
    {
        return $this->db->lastError();
    }

    /**
     * Invoke callback
     *
     * Invokes the the callback in the order that they are stored in the callback
     * array for a passed in callback type. All additional parameters are sent to
     * the invoked callback.
     *
     * @param string $name Name of the callback
     * @param bool $before Invoke 'before' callables of '$name' callback, default self::CALLBACK_BEFORE
     * @return void
     */
    protected function invokeCallback(string $name, bool $before = self::CALLBACK_BEFORE)
    {
        $name = ($before ? "before" : "after") . ucfirst($name);
        if (isset($this->{$name}) === false || is_array($this->{$name}) === false) {
            // @todo: throw exception
            return;
        }

        $params = array_slice(func_get_args(), 2);
        foreach ($this->{$name} as $callable) {
            $callable(...$params);
        }
    }

    /**
     * Set table name
     *
     * Sets the table name based on the model class name. It discards the whole
     * namespace, and uses only the class name. The class name is pluralized, if
     * defined so by the 'pluralizeTableName' configuration option. It will also
     * transform the name into the right format, based on the 'tableNameStyle' configuration
     * option.
     *
     * @return void
     */
    protected function setTable()
    {
        $this->table = get_class($this);
        if (($pos = strrpos($this->table, "\\")) !== false) {
            $this->table = substr($this->table, $pos + 1);
        }

        if ($this->config["database.pluralizeTableName"]) {
            $this->table = $this->inflector->pluralize($this->table);
        }

        switch ($this->config["database.tableNameStyle"]) {
            case self::TBL_NAME_CAMEL_UCFIRST:
                $this->table = $this->inflector->camelize($this->table, Inflector::UPCASE_FIRST_LETTER);
                break;
            case self::TBL_NAME_CAMEL_LCFIRST:
                $this->table = $this->inflector->camelize($this->table, Inflector::DOWNCASE_FIRST_LETTER);
                break;
            case self::TBL_NAME_UNDERSCORE:
                $this->table = $this->inflector->underscore($this->table);
                break;
            case self::TBL_NAME_UPPERCASE:
                $this->table = strtoupper($this->table);
                break;
            case self::TBL_NAME_LOWERCASE:
                $this->table = strtolower($this->table);
                break;
        }
    }

    /**
     * Set soft deletion
     *
     * Sets the soft deletion options to class properties from the configuration.
     *
     * @return void
     */
    protected function setSoftDelete()
    {
        $softDelete = $this->config["database.softDelete"];
        $this->softDelete = $softDelete["enabled"] ?? false;
        $this->delCol = $softDelete["column"] ?? "";
        $this->delValType = $softDelete["value"] ?? 0;
    }
}
