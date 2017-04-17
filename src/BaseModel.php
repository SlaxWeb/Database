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
use SlaxWeb\Database\Query\Builder;
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Config\Container as Config;
use SlaxWeb\Hooks\Container as HooksContainer;
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
     * Hook invokation type
     */
    const HOOK_BEFORE = "before";
    const HOOK_AFTER = "after";

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
     * Primary Key Column
     *
     * @var string
     */
    protected $primKey = "";

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
     * Query Builder
     *
     * @var \SlaxWeb\Database\Query\Builder
     */
    protected $qBuilder = null;

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
     * Timestamps
     *
     * @var bool
     */
    protected $timestamps = null;

    /**
     * Created column
     *
     * @var string
     */
    protected $createdColumn = "";

    /**
     * Updated column
     *
     * @var string
     */
    protected $updatedColumn = "";

    /**
     * Timestamp function
     *
     * @var string
     */
    protected $timestampFunction = "";

    /**
     * Hooks Container
     *
     * @var \SlaxWeb\Hooks\Container
     */
    protected $hooks = null;

    /**
     * Custom name for the hooks triggered in this or extended class.
     * @var null
     */
    protected $hookName = null;

    /**
     * Class constructor
     *
     * Initialize the Base Model, by storging injected dependencies into class properties.
     *
     * @param \Psr\Log\LoggerInterface $logger PSR-7 compliant logger object
     * @param \SlaxWeb\Config\Container $config Configuration container object
     * @param \ICanBoogie\Inflector $inflector Inflector object for pluralization and word transformations
     * @param \SlaxWeb\Database\Query\Builder $queryBuilder Query Builder instance
     * @param \SlaxWeb\Database\Interface\Library $db Database library object
     * @param \SlaxWeb\Hooks\Container $hooks HooksContainer hooks container object
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Inflector $inflector,
        Builder $queryBuilder,
        Database $db,
        HooksContainer $hooks
    ) {

        $this->logger = $logger;
        $this->config = $config;
        $this->inflector = $inflector;
        $this->qBuilder = $queryBuilder;
        $this->db = $db;
        $this->hooks = $hooks;
        
        $this->invokeHook("init");

        if ($this->table === "" && $this->config["database.autoTable"]) {
            $this->setTable();
        }
        $this->setSoftDelete();
        $this->setTimestampConfig();

        $this->logger->info("Model initialized successfuly", ["model" => get_class($this)]);

        $this->invokeHook("init", self::HOOK_AFTER);
    }

    /**
     * Get Primary Key
     *
     * Gets the primary key column name of the model.
     *
     * @return string
     */
    public function getPrimKey(): string
    {
        return $this->primKey;
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
        $this->invokeHook("create");

        if ($this->timestamps) {
            $data[$this->createdColumn] = ["func" => $this->timestampFunction];
        }

        $query = $this->qBuilder->table($this->table)->insert($data);
        if (($status = $this->db->execute($query, $this->qBuilder->getParams())) === false) {
            $this->error = $this->db->lastError();
        }
        $this->qBuilder->reset();

        $this->invokeHook("create", self::HOOK_AFTER);
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
        $this->invokeHook("select");

        $query = $this->qBuilder->table($this->table)->select($columns);
        $this->db->execute($query, $this->qBuilder->getParams());
        $this->result = $this->db->fetch();
        $this->qBuilder->reset();

        $this->invokeHook("select", self::HOOK_AFTER);
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
        $this->invokeHook("update");

        if ($this->timestamps) {
            $columns[$this->updatedColumn] = ["func" => $this->timestampFunction];
        }

        $query = $this->qBuilder->table($this->table)->update($columns);
        if (($status = $this->db->execute($query, $this->qBuilder->getParams())) === false) {
            $this->error = $this->db->lastError();
        }
        $this->qBuilder->reset();

        $this->invokeHook("update", self::HOOK_AFTER);
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
        $this->invokeHook("delete");

        if ($this->softDelete) {
            $val = $this->delValType === self::SDEL_VAL_TIMESTAMP
                ? ["func" => "NOW()"]
                : true;
            $status = $this->update([$this->delCol => $val]);
        } else {
            $query = $this->qBuilder->table($this->table)->delete();
            if (($status = $this->db->execute($query)) === false) {
                $this->error = $this->db->lastError();
            }
            $this->qBuilder->reset();
        }

        $this->invokeHook("delete", self::HOOK_AFTER);
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
        $this->qBuilder->where($column, $value, $opr, "AND");
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
        $this->qBuilder->where($column, $value, $opr, "OR");
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
        $this->qBuilder->groupWhere($predicates, "AND");
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
        $this->qBuilder->groupWhere($predicates, "OR");
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
        $this->qBuilder->nestedWhere($column, $nested, $lOpr, "AND");
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
        $this->qBuilder->nestedWhere($column, $nested, $lOpr, "OR");
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
        $this->qBuilder->join($table, $type);
        return $this;
    }

    /**
     * Model Join
     *
     * Construct a join statement from a model object. The method automatically
     * obtains the primary key column name from the joining model, construcing a
     * basic join statement for that models table automagically. If the joining
     * model does not have a primary key column name set an exception is thrown.
     *
     * @param \SlaxWeb\Database\BaseModel $model Joining model instance
     * @param string $forKey Foreign key against which the joining models primary key is matched
     * @param string $type Join type, default string("INNER JOIN")
     * @param string $cOpr Comparison operator for the two keys
     * @return self
     *
     * @exceptions \SlaxWeb\Database\Exception\NoPrimKeyException
     */
    public function joinModel(
        BaseModel $model,
        string $forKey,
        string $type = "INNER JOIN",
        string $cOpr = "="
    ): self {
        $primKey = $model->getPrimKey();
        if (empty($primKey)) {
            throw new Exception\NoPrimKeyException;
        }

        return $this->join($model->table, $type)->joinCond($primKey, $forKey, $cOpr);
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
     * @return self
     */
    public function joinCond(string $primKey, string $forKey, string $cOpr = "="): self
    {
        $this->qBuilder->joinCond($primKey, $forKey, $cOpr, "AND");
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
     * @return self
     */
    public function orJoinCond(string $primKey, string $forKey, string $cOpr = "="): self
    {
        $this->qBuilder->joinCond($primKey, $forKey, $cOpr, "OR");
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
        $this->qBuilder->joinCols($cols);
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
        $this->qBuilder->groupBy($col);
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
        $this->qBuilder->orderBy($col, $direction, $func);
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
        $this->qBuilder->limit($limit, $offset);
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
     * Invoke hook
     *
     * Invokes the hook specified by the name. The whole hook name consists of string
     * model, class name or custom hook name stored in the protected property hookName,
     * before or after concatenated with the modelMethod. Example: "model.user.before.init"
     * for a user model.
     *
     * @param string $modelMethod Model method name.
     * @param string $before Invoke 'before' '$modelMethod' hook, default self::HOOK_BEFORE
     * @return void
     */
    protected function invokeHook(string $modelMethod, string $before = self::HOOK_BEFORE)
    {
        $clsName = $this->hookName ?? $this->getClassName();
        $name = "model.{$clsName}.{$before}.{$modelMethod}";

        $this->hooks->exec($name);
    }

    /**
     * Returns curent, lowercased class name without namespace.
     * @return string Lowercased class name.
     */
    protected function getClassName() {
        $splitedName = explode("\\", get_class($this));
        return strtolower($splitedName[count($splitedName) - 1]);
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

    /**
     * Set Timestamp Config
     *
     * Sets the timestamp configuration to the class properties. Any previously
     * user set values to the configuration properties are not overwritten.
     *
     * @return void
     */
    protected function setTimestampConfig()
    {
        $timestamp = $this->config["database.timestamp"];
        $this->timestamps = $this->timestamps === null
            ? ($timestamp["enabled"] ?? false)
            : $this->timestamps;
        $this->createdColumn = $this->createdColumn ?: ($timestamp["createdColumn"] ?? "created_at");
        $this->updatedColumn = $this->updatedColumn ?: ($timestamp["updatedColumn"] ?? "updated_at");
        $this->timestampFunction = $this->timestampFunction ?: ($timestamp["function"] ?? "NOW()");
    }
}
