#!/bin/bash
PATH=~/.composer/vendor/bin:$PATH

if [ ! -f ~/.composer/vendor/bin/phpunit ]; then
  echo "Running composer installation..."
  composer install
  composer global require 'phpunit/phpunit=4.1.*'
fi

echo "Running PHPUnit suite..."
phpunit --configuration ./phpunit.xml