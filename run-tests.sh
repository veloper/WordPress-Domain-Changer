#!/bin/bash -l

source ~/.bashrc

PATH=~/.composer/vendor/bin:$PATH

if [ ! -f ~/.composer/vendor/bin/phpunit ]; then
  echo "Running composer installation..."
  php composer.phar global install
  php composer.phar global require 'phpunit/phpunit=4.1.*'
fi

echo "Running PHPUnit suite..."
phpunit --configuration phpunit.xml

PHPUNIT_EXIT_CODE=$?
echo "PHPUnit Exit Code: $PHPUNIT_EXIT_CODE"

echo "Running RSpec/Capybara suite..."
echo `pwd`
bundle install
bundle exec rake

RSPEC_EXIT_CODE=$?
echo "RSpec Exit Code: $PHPUNIT_EXIT_CODE"

exit $[$PHPUNIT_EXIT_CODE + $RSPEC_EXIT_CODE];
