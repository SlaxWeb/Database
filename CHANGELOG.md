# ChangeLog

Changes between versions

## Current changes

* Soft deletion
* Model joining

## v0.4

### v0.4.1

* fix subcomponent 'database-pdo' version in component metadata file

### v0.4.0

* Model Loader Service renamed to 'loadDBModel.service', 'loadModel.service' deprecated
* Added port configuration option to connection config
* Removed 'leftJoin', 'rightJoin', 'fullJoin', and 'crossJoin' in favour of one
'join' method with join type as input
