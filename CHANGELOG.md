# Change Log

## 2.0.0 - November 19th

This release features a complete overhaul and rewrite of WordPress Domain Changer.

* Added ability to select individual database tables
* Added a preview & confirm step where every SQL query can be viewed before execution
* Added more descriptive error, notice, info, and success flash messages
* Added multi-byte character support
* Added comments via Disqus.com embedded iframe
    * To protect users' privacy the `<iframe>` and associated JavaScript are not inserted into the DOM until the "Load & Participate In Conversation" button is clicked
* Added PayPal donation links (see context below)
    * > If you've found this script useful please consider treating me to a **Pint of Beer** or a **Cup of Coffee**
* Added RSpec/Capybara integration testing
* Added tests for every WordPress version between `2.0.10` and `4.0.0`
* Added continuous integration testing -- Travis CI
* Set `mb_internal_encoding` to `UTF-8`
* Set `error_reporting` to `E_ALL`
* Set `set_time_limit` to 60 seconds
* Refactored code base over to a minimalistic MVC pattern
* Refactored MySQL query generation and execution to be more granular
* Refactored the PHP-Serialized-String find & replace logic to a more simplistic & robust.
* Fixed major bug where the last occurrence of `$find` was not replaced when it occurred multiples times within a substring of a PHP-Serialized-String
* Updated session lifetime from 5 to 10 minutes since last request
* Updated the user experience to follow more of a wizard-based approach
* Updated `README.md` instructions
* Updated version to 2.0.0

## 0.2.0 - August 7th

* Added basic `Controller` and `View` classes
* Fixed path related issues that were reported in the 0.1.0 release
* Fixed new domain auto-detect now accounts for https urls
* Broke out the serialization logic into it's own helper class
    * `new SerializedString("serialized:string").replace($find, $replace).toString()`
* Update to `phpunit 4.1.*` via the `composer.json` file
* Added a `test.sh` script to simplify testing via `phpunit` and `composer` setup.
* Updated `README.md`
    * Contributors
    * Testing Instructions
    * Grammar

## 0.1.0 - August 7th

* Started `CHANGELOG.md` file.
