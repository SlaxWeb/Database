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
use SlaxWeb\Database\Query\Builder;
use Psr\Log\LoggerInterface as Logger;
use SlaxWeb\Config\Container as Config;
use SlaxWeb\Database\Interfaces\Result;
use SlaxWeb\Hooks\Container as HooksContainer;
use SlaxWeb\Database\Interfaces\Library as Database;

class BaseModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Logger
     *
     * @var LoggerMock
     */
    protected $logger = null;

    /**
     * Config
     *
     * @var ConfigMock
     */
    protected $config = null;

    /**
     * Inflector
     *
     * @var InflectorMock
     */
    protected $inflector = null;

    /**
     * Query Builder
     *
     * @var BuilderMock
     */
    protected $builder = null;

    /**
     * Database Library
     *
     * @var LibraryMock
     */
    protected $db = null;

    /**
     * Hooks Container
     * @var HooksContainerMock
     */
    protected $hooks = null;

    /**
     * Set up test
     *
     * Create dependency mocks for use in tests later.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMockClassName("LoggerMock")
            ->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(["offsetGet"])
            ->setMockClassName("ConfigMock")
            ->getMock();

        $this->inflector = $this->getMockBuilder(Inflector::class)
            ->disableOriginalConstructor()
            ->setMethods(["pluralize", "camelize", "underscore"])
            ->setMockClassName("InflectorMock")
            ->getMock();

        $this->builder = $this->getMockBuilder(Builder::class)
            ->setMethods(["insert", "update", "delete", "select", "getParams"])
            ->setMockClassName("BuilderMock")
            ->getMock();

        $this->db = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "lastError"])
            ->setMockClassName("DatabaseMock")
            ->getMockForAbstractClass();

        $this->hooks = $this->getMockBuilder(HooksContainer::class)
            ->disableOriginalConstructor()
            ->setMethods(["exec"])
            ->setMockClassName("HooksContainerMock")
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
            ->setMethods(["setSoftDelete", "setTimestampConfig"])
            ->setMockClassName("Test")
            ->getMock();

        $this->config->expects($this->exactly(22))
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

        $this->inflector->expects($this->once())
            ->method("pluralize")
            ->with("Test")
            ->willReturn("Tests");

        $this->inflector->expects($this->exactly(2))
            ->method("camelize")
            ->withConsecutive(
                ["Test", Inflector::UPCASE_FIRST_LETTER],
                ["Test", Inflector::DOWNCASE_FIRST_LETTER]
            )->will($this->onConsecutiveCalls("Test", "test"));

        $this->inflector->expects($this->once())
            ->method("underscore")
            ->with("Test")
            ->willReturn("test");

        $expectations = [
            // Table name already set
            "PreSetTable",
            // Config denies table setting
            "",
            // Table name set
            "Test",
            // Table name pluralized
            "Tests",
            // Table name camelized, ucfirst
            "Test",
            // Table name camelized, lcfirst
            "test",
            // Table name underscore
            "test",
            // Table name uppercase
            "TEST",
            // Table name lowercase
            "test"
        ];
        $model->table = "PreSetTable";
        foreach ($expectations as $exp) {
            $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
            $this->assertEquals($exp, $model->table);
            $model->table = "";
        }
    }

    /**
     * Test create method
     *
     * Create method must call 'insert' with unmodified input array, and call 'lastError'
     * of the database library object if return value equals false.
     *
     * @return void
     */
    public function testCreate()
    {
        $row = ["foo" => "bar"];

        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $this->builder->expects($this->any())
            ->method("insert")
            ->with($row)
            ->willReturn("insert statement");

        $this->builder->expects($this->any())
            ->method("getParams")
            ->willReturn([]);

        $this->db->expects($this->exactly(2))
            ->method("execute")
            ->with("insert statement", [])
            ->will($this->onConsecutiveCalls(true, false));

        $this->db->expects($this->once())
            ->method("lastError");

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertTrue($model->create($row));
        $this->assertFalse($model->create($row));
    }

    /**
     * Test update method
     *
     * Update method must call 'update' with an unmodified input array and call
     * 'lastError' of the database library object if return value equals false.
     *
     * @return void
     */
    public function testUpdate()
    {
        $row = ["foo" => "bar"];

        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $this->builder->expects($this->any())
            ->method("update")
            ->with($row)
            ->willReturn("update statement");

        $this->builder->expects($this->any())
            ->method("getParams")
            ->willReturn([]);

        $this->db->expects($this->exactly(2))
            ->method("execute")
            ->with("update statement", [])
            ->will($this->onConsecutiveCalls(true, false));

        $this->db->expects($this->once())
            ->method("lastError");

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertTrue($model->update($row));
        $this->assertFalse($model->update($row));
    }

    /**
     * Test delete method
     *
     * Delete method must call 'delete' with an unmodified input array and call
     * 'lastError' of the database library object if return value equals false.
     *
     * @return void
     */
    public function testDelete()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $this->builder->expects($this->any())
            ->method("delete")
            ->willReturn("delete statement");

        $this->db->expects($this->exactly(2))
            ->method("execute")
            ->with("delete statement")
            ->will($this->onConsecutiveCalls(true, false));

        $this->db->expects($this->once())
            ->method("lastError");

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertTrue($model->delete());
        $this->assertFalse($model->delete());
    }

    /**
     * Test No Result
     *
     * Ensure the model will raise appropriate exceptions if no results have yet
     * been obtained, but an attempt to access them was made
     *
     * @return void
     */
    public function testNoResult()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);

        try {
            $model->next();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\SlaxWeb\Database\Exception\NoDataException::class, $e);
        }
    }

    /**
     * Test data retrieval
     *
     * Ensure 'select' method calls the appropirate methods in the library, returns
     * and returns the result object.
     *
     * @return void
     *
     * @depends testNoResult
     */
    public function testDataRetrieval()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $result = $this->createMock(Result::class);

        $this->builder->expects($this->any())
            ->method("select")
            ->with(["col1"])
            ->willReturn("select statement");

        $this->builder->expects($this->any())
            ->method("getParams")
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method("execute")
            ->with("select statement", [])
            ->willReturn(true);

        $this->db->expects($this->once())
            ->method("fetch")
            ->willReturn($result);

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertInstanceOf(Result::class, $model->select(["col1"]));
    }

    /**
     * Test result handling
     *
     * Ensure the result handling methods are forwarded to the Result object in
     * the BaseModel.
     *
     * @return void
     *
     * @depends testDataRetrieval
     */
    public function testResultHandling()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("Test")
            ->getMock();

        $result = $this->getMockBuilder(Result::class)
            ->setMethods(["__get", "next", "prev", "row", "rowCount", "getResults", "get"])
            ->getMock();

        // ensure all result methods are called once through their base model counterparts
        $result->expects($this->once())
            ->method("__get")
            ->with("col1");
        $result->expects($this->once())
            ->method("next");
        $result->expects($this->once())
            ->method("prev");
        $result->expects($this->once())
            ->method("row")
            ->with(1);
        $result->expects($this->once())
            ->method("rowCount");
        $result->expects($this->once())
            ->method("getResults");
        $result->expects($this->once())
            ->method("get");

        $this->builder->expects($this->any())
            ->method("select")
            ->with(["col1"])
            ->willReturn("select statement");

        $this->builder->expects($this->any())
            ->method("getParams")
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method("execute")
            ->with("select statement", [])
            ->willReturn(true);

        $this->db->expects($this->once())
            ->method("fetch")
            ->willReturn($result);

        $model->table = "TestTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $model->select(["col1"]);

        $model->col1;
        $model->next();
        $model->prev();
        $model->row(1);
        $model->rowCount();
        $model->getResults();
        $model->get();
    }

    /**
     * Test soft deletes
     *
     * Ensure the soft deletion will update the correct column with the correct
     * value.
     *
     * @return void
     */
    public function testSoftDelete()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(["update", "setTimestampConfig"])
            ->getMock();

        $model->expects($this->exactly(2))
            ->method("update")
            ->withConsecutive(
                [["deleted" => true]],
                [["del" => ["func" => "NOW()"]]]
            )->willReturn(true);

        $this->config->expects($this->exactly(2))
            ->method("offsetGet")
            ->will(
                $this->onConsecutiveCalls([
                    "enabled"   =>  true,
                    "column"    =>  "deleted",
                    "value"     =>  BaseModel::SDEL_VAL_BOOL
                ], [
                    "enabled"   =>  true,
                    "column"    =>  "del",
                    "value"     =>  BaseModel::SDEL_VAL_TIMESTAMP
                ])
            );

        $model->table = "TestTable";

        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertTrue($model->delete());
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $this->assertTrue($model->delete());
    }

    /**
     * Test timestamps
     *
     * Ensure that timestamping works when inserting and/or updating data in the
     * database.
     *
     * @return void
     */
    public function testTimestamps()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(["setSoftDelete"])
            ->getMock();

        $this->builder->expects($this->once())
            ->method("insert")
            ->with(["foo" => "bar", "insCol" => ["func" => "FOO()"]])
            ->willReturn(true);

        $this->builder->expects($this->once())
            ->method("update")
            ->with(["foo" => "baz", "updCol" => ["func" => "FOO()"]])
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method("offsetGet")
            ->willReturn([
                "enabled"   =>  true,
                "createdColumn" =>  "insCol",
                "updatedColumn" =>  "updCol",
                "function"      =>  "FOO()"
            ]);

        $model->table = "TestTable";

        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $model->create(["foo" => "bar"]);
        $model->update(["foo" => "baz"]);
    }

    /**
     * Test model joining
     *
     * Ensure that a proper join statement is constructed with the model joining
     * technique.
     *
     * @return void
     */
    public function testModelJoin()
    {
        $model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(["join", "joinCond"])
            ->getMock();

        $joinModel = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->setMethods(["getPrimKey"])
            ->getMock();

        $model->expects($this->once())
            ->method("join")
            ->with("JoinTable", "LEFT OUTTER JOIN")
            ->willReturn($model);

        $model->expects($this->once())
            ->method("joinCond")
            ->with("id", "joinModel_id", "=")
            ->willReturn($model);

        $joinModel->expects($this->exactly(2))
            ->method("getPrimKey")
            ->will($this->onConsecutiveCalls("", "id"));

        $model->table = "TestTable";
        $joinModel->table = "JoinTable";
        $model->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);
        $joinModel->__construct($this->logger, $this->config, $this->inflector, $this->builder, $this->db, $this->hooks);

        $exception = false;
        try {
            $model->joinModel($joinModel, "joinModel_id", "=");
        } catch (\Throwable $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            \SlaxWeb\Database\Exception\NoPrimKeyException::class,
            $exception
        );

        $model->joinModel($joinModel, "joinModel_id", "LEFT OUTTER JOIN", "=");
    }
}
