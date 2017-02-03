# ChangeLog

Changes between versions

## Current changes

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
