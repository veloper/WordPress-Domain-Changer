#!/bin/bash -l

echo "Bundle Install..."
bundle install

echo "Composer Install..."
php composer.phar global install
php composer.phar global require 'phpunit/phpunit=4.1.*'


echo "WordPress Versions Download..."
ruby -e 'require "spec/support/word_press_util"; WordPressUtil.download_archives!'
