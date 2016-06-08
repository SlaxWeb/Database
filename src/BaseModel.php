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

abstract class BaseModel
{
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
        $this->_logger = $logger;
        $this->_config = $config;
        $this->_inflector = $inflector;
        $this->_db = $db;

        $this->_logger->info("Model initialized successfuly", ["model" => get_class($this)]);
    }
}
