<?php
/**
 * Base Model Test
 *
 * Test ensures that the abstract base model functions properly and will not cause
 * issues when used in user defined models.
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\Database\Test\Unit;

use ICanBoogie\Inflector;
use SlaxWeb\Database\BaseModel;
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Config\Container as Config;
use SlaxWeb\Database\LibraryInterface as Database;

class BaseModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Logger
     *
     * @var LoggerMock
     */
    protected $_logger = null;

    /**
     * Config
     *
     * @var ConfigMock
     */
    protected $_config = null;

    /**
     * Inflector
     *
     * @var InflectorMock
     */
    protected $_inflector = null;

    /**
     * Database Library
     *
     * @var LibraryMock
     */
    protected $_db = null;

    /**
     * Set up test
     *
     * Create dependency mocks for use in tests later.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->_logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMockClassName("LoggerMock")
            ->getMockForAbstractClass();

        $this->_config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(["offsetGet"])
            ->setMockClassName("ConfigMock")
            ->getMock();

        $this->_inflector = $this->getMockBuilder(Inflector::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("InflectorMock")
            ->getMock();

        $this->_db = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("DatabaseMock")
            ->getMockForAbstractClass();
    }

    protected function tearDown()
    {
    }

    public function testModelInit()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
    }
}
