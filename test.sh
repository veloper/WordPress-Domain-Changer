#!/bin/bash
PATH=~/.composer/vendor/bin:$PATH

if [ ! -f ~/.composer/vendor/bin/phpunit ]; then
  echo "Running composer installation..."
  composer global install
  composer global require 'phpunit/phpunit=4.1.*'
fi

WPDC_PATH=`pwd`

echo "Running PHPUnit suite..."
cd "$WPDC_PATH/tests/phpunit"
phpunit --configuration ./phpunit.xml

PHPUNIT_EXIT_CODE=$?

# echo "Running PHPUnit suite..."
# cd "$WPDC_PATH/tests/rspec"
# rspec spec

RSPEC_EXIT_CODE=$?


exit if [  ]