# Change Log


## 0.2.0

* Added basic Controller and View classes.
* Fixed pathing issues that were reported in the 0.1.0 release.
* Fixed new domain auto-detect now accounts for https urls.
* Broke out the serialization logic into it's own helper class.
    * `new SerializedString("serialized:string").replace($find, $replace).toString()`
* Update to `phpunit 4.1.*` via the `composer.json` file.
* Added a `test.sh` script to simplify testing via `phpunit` and `composer` setup.
* Updated `README.md`
    * Contributors
    * Testing Instructions
    * Grammar

## 0.1.0

* Started `CHANGELOG.md` file.