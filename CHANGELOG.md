# ChangeLog

Changes between versions

## v0.6

### v0.6.1

* throw an exception if an attempt to load a model without a name is made
* move the model loader into a regular 'dbModelLoader.service' service to enable
extending the loader - old 'loadDBModel.service' is still available as before, and
should be used as before

### v0.6.0

* add connection timeout configuration option
* add query builder to the main database component
* base model callbacks are now handled through the hooks system

## v0.5

### v0.5.0

* Soft deletion
* Model joining
* Save new model object to app properties in service provider and return it on next
call

## v0.4

### v0.4.1

* fix subcomponent 'database-pdo' version in component metadata file

### v0.4.0

* Model Loader Service renamed to 'loadDBModel.service', 'loadModel.service' deprecated
* Added port configuration option to connection config
* Removed 'leftJoin', 'rightJoin', 'fullJoin', and 'crossJoin' in favour of one
'join' method with join type as input
