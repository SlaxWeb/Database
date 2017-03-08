<?php
/**
 * Database Component Config
 *
 * Database Component Configuration file
 *
 * @package   SlaxWeb\Database
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
use SlaxWeb\Database\BaseModel;
use SlaxWeb\Database\Interfaces\Library as Driver;

/*
 * Connection Data
 *
 * Available drivers:
 * Driver::DB_CUBRID    =   cubrid
 * Driver::DB_DBLIB     =   dblib
 * Driver::DB_FIREBIRD  =   firebird
 * Driver::DB_IBM       =   ibm
 * Driver::DB_INFORMIX  =   informix
 * Driver::DB_MYSQL     =   mysql (default)
 * Driver::DB_OCI       =   oci
 * Driver::DB_ODBC      =   odbc
 * Driver::DB_PGSQL     =   pgsql
 * Driver::DB_SQLITE    =   sqlite
 * Driver::DB_SQLSRV    =   sqlsrv
 * Driver::DB_4D        =   4d
 */
$configuration["connection"] = [
    "driver"    =>  Driver::DB_MYSQL,
    "hostname"  =>  "localhost",
    "port"      =>  0,
    "database"  =>  "",
    "username"  =>  "",
    "password"  =>  "",
    "timeout"   =>  10
];

/*
 * Soft delete
 *
 * Soft deleting does not delete data from the Database, but merely marks it as
 * deleted by writing a value in the database. The configuration here defined should
 * the soft deletion be used at all, the column name, and what value shoul be written
 * to said column.
 *
 * For values the following options are available:
 * - BaseModel::SDEL_VAL_BOOL - sets the soft delete column to bool value 'true'
 * - BaseModel::SDEL_VAL_TIMESTAMP - sets the soft delete column to current timestamp
 */
$configuration["softDelete"] = [
    "enabled"   =>  false,
    "column"    =>  "deleted",
    "value"     =>  BaseModel::SDEL_VAL_BOOL
];

/*
 * Automatic timestamps
 *
 * The BaseModel offers automatic timestamping for newly created or updated rows.
 * By default this is disabled and has to be enabled here.
 * Along with enabling you have the option to set the column names for the 'created'
 * and 'updated' timestamps, as well as the function to be executed in the query.
 */
$configuration["timestamp"] = [
    "enabled"       =>  false,
    "createdColumn" =>  "created_at",
    "updatedColumn" =>  "updated_at",
    "function"      =>  "NOW()"
];

/*
 * Automatically set table name if not set before
 */
$configuration["autoTable"] = true;

/*
 * Pluralize model class name for automatic table name setting.
 */
$configuration["pluralizeTableName"] = true;

/*
 * Convert table name format when automatically setting table name.
 *
 * Available options are:
 * - false - same format as class name
 * - BaseModel::TBL_NAME_CAMEL_UCFIRST - CamelizedUpperCaseFirst
 * - BaseModel::TBL_NAME_CAMEL_LCFIRST - camelizedLowerCaseFirst (default)
 * - BaseModel::TBL_NAME_UNDERSCORE - separated_by_underscores
 * - BaseModel::TBL_NAME_UPPERCASE - ALLUPPERCASE
 * - BaseModel::TBL_NAME_LOWERCASE - alllowercase
 */
$configuration["tableNameStyle"] = BaseModel::TBL_NAME_CAMEL_LCFIRST;

/*
 * Model class namespace
 *
 * The namespace in which the Model classes are defined.
 *
 * NOTE: If you change the autoloader config, you have to change this configuration
 * as well. If you fail to do so, the "Model Class Loader" will no longer work.
 */
$configuration["classNamespace"] = "\\App\\Model\\";
