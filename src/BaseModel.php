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
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Config\Container as Config;
use SlaxWeb\Database\LibraryInterface as Database;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

abstract class BaseModel
{
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
    protected $_logger = null;

    /**
     * Config object
     *
     * @var \SlaxWeb\Config\Container
     */
    protected $_config = null;

    /**
     * Inflector object
     *
     * @var \ICanBoogie\Inflector
     */
    protected $_inflector = null;

    /**
     * Database Library
     *
     * @var \SlaxWeb\Database\LibraryInterface
     */
    protected $_db = null;

    /**
     * Last Query Error object
     *
     * @var \SlaxWeb\Database\Error
     */
    protected $_error = null;

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
    protected $_beforeInit = [];
    protected $_afterInit = [];
    protected $_beforeCreate = [];
    protected $_afterCreate = [];
    protected $_beforeRead = [];
    protected $_afterRead = [];
    protected $_beforeUpdate = [];
    protected $_afterUpdate = [];
    protected $_beforeDelete = [];
    protected $_afterDelete = [];

    /**
     * Class constructor
     *
     * Initialize the Base Model, by storging injected dependencies into class properties.
     *
     * @param \Psr\Log\LoggerInterface $logger PSR-7 compliant logger object
     * @param \SlaxWeb\Config\Container $config Configuration container object
     * @param \ICanBoogie\Inflector $inflector Inflector object for pluralization and word transformations
     * @param \SlaxWeb\Database\LibraryInterface $db Database library object
     * @return void
     */
    public function __construct(Logger $logger, Config $config, Inflector $inflector, Database $db)
    {
        $this->_invokeCallback("init");

        $this->_logger = $logger;
        $this->_config = $config;
        $this->_inflector = $inflector;
        $this->_db = $db;

        if ($this->table === "" && $this->_config["database.autoTable"]) {
            $this->_setTable();
        }

        $this->_logger->info("Model initialized successfuly", ["model" => get_class($this)]);

        $this->_invokeCallback("init", self::CALLBACK_AFTER);
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
        if (($status = $this->_db->insert($this->table, $data)) === false) {
            $this->_error = $this->_db->lastError();
        }
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
        return $this->_db->select($this->table, $columns);
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
    public function where(string $column, $value, string $opr = Predicate::OPR_EQUAL): self
    {
        $this->_db->where($column, $value, $opr);
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
    public function orWhere(string $column, $value, string $opr = Predicate::OPR_EQUAL): self
    {
        $this->_db->where($column, $value, $opr, "OR");
        return $this;
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
    protected function _invokeCallback(string $name, bool $before = self::CALLBACK_BEFORE)
    {
        $name = ($before ? "before" : "after") . ucfirst($name);
        $property = "_{$name}";
        if (isset($this->{$property}) === false || is_array($this->{$property}) === false) {
            // @todo: throw exception
            return;
        }

        $params = array_slice(func_get_args(), 2);
        foreach ($this->{$property} as $callable) {
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
    protected function _setTable()
    {
        $this->table = get_class($this);
        if (($pos = strrpos($this->table, "\\")) !== false) {
            $this->table = substr($this->table, $pos + 1);
        }

        if ($this->_config["database.pluralizeTableName"]) {
            $this->table = $this->_inflector->pluralize($this->table);
        }

        switch ($this->_config["database.tableNameStyle"]) {
            case self::TBL_NAME_CAMEL_UCFIRST:
                $this->table = $this->_inflector->camelize($this->table, Inflector::UPCASE_FIRST_LETTER);
                break;
            case self::TBL_NAME_CAMEL_LCFIRST:
                $this->table = $this->_inflector->camelize($this->table, Inflector::DOWNCASE_FIRST_LETTER);
                break;
            case self::TBL_NAME_UNDERSCORE:
                $this->table = $this->_inflector->underscore($this->table);
                break;
            case self::TBL_NAME_UPPERCASE:
                $this->table = strtoupper($this->table);
                break;
            case self::TBL_NAME_LOWERCASE:
                $this->table = strtolower($this->table);
                break;
        }
    }
}
