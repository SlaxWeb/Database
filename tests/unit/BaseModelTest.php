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
            ->setMethods(["pluralize", "camelize", "underscore"])
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

    /**
     * Test Table Name Auto Setter
     *
     * Ensure that the table name is set when permitted by config, and not set before,
     * and the name is pluralized and put into correct form, based on config.
     *
     * @return void
     */
    public function testTableNameSet()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $this->_config->expects($this->exactly(22))
            ->method("offsetGet")
            ->will(
                $this->onConsecutiveCalls(
                    // deny table setting
                    false,

                    // allow table setting, no pluralization or format manipulation
                    true,
                    false,
                    false,

                    // allow table setting, pluralize, no format manipulation
                    true,
                    true,
                    false,

                    // allow table setting, no pluralization, camel case with ucfirst
                    true,
                    false,
                    BaseModel::TBL_NAME_CAMEL_UCFIRST,

                    // allow table setting, no pluralization, camel case with lcfirst
                    true,
                    false,
                    BaseModel::TBL_NAME_CAMEL_LCFIRST,

                    // allow table setting, no pluralization, underscore
                    true,
                    false,
                    BaseModel::TBL_NAME_UNDERSCORE,

                    // allow table setting, no pluralization, uppercase
                    true,
                    false,
                    BaseModel::TBL_NAME_UPPERCASE,

                    // allow table setting, no pluralization, lowercase
                    true,
                    false,
                    BaseModel::TBL_NAME_LOWERCASE
                )
            );

        $this->_inflector->expects($this->once())
            ->method("pluralize")
            ->with("Test")
            ->willReturn("Tests");

        $this->_inflector->expects($this->exactly(2))
            ->method("camelize")
            ->withConsecutive(
                ["Test", Inflector::UPCASE_FIRST_LETTER],
                ["Test", Inflector::DOWNCASE_FIRST_LETTER]
            )->will($this->onConsecutiveCalls("Test", "test"));

        $this->_inflector->expects($this->once())
            ->method("underscore")
            ->with("Test")
            ->willReturn("test");

        // Table name already set
        $model->table = "PreSetTable";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("PreSetTable", $model->table);

        // Config denies table setting
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("", $model->table);

        // Table name set
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("Test", $model->table);

        // Table name pluralized
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("Tests", $model->table);

        // Table name camelized, ucfirst
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("Test", $model->table);

        // Table name camelized, lcfirst
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("test", $model->table);

        // Table name underscore
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("test", $model->table);

        // Table name uppercase 
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("TEST", $model->table);

        // Table name lowercase
        $model->table = "";
        $model->__construct($this->_logger, $this->_config, $this->_inflector, $this->_db);
        $this->assertEquals("test", $model->table);
    }
}
