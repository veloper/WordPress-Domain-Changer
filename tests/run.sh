#!/bin/bash -l

PATH=~/.composer/vendor/bin:$PATH

echo "Running PHPUnit suite..."
phpunit --configuration phpunit.xml

PHPUNIT_EXIT_CODE=$?
echo "PHPUnit Exit Code: $PHPUNIT_EXIT_CODE"

echo "Running RSpec/Capybara suite..."
bundle exec rspec spec -fd --color

RSPEC_EXIT_CODE=$?
echo "RSpec Exit Code: $PHPUNIT_EXIT_CODE"

exit $[$PHPUNIT_EXIT_CODE + $RSPEC_EXIT_CODE];
