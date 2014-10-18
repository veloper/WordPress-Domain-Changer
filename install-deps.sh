#!/bin/bash -l

echo "Composer Install..."
php composer.phar global install
php composer.phar global require 'phpunit/phpunit=4.1.*'

echo "WordPress Versions Download..."
ruby -e 'require File.expand_path("../spec/spec_helper.rb", __FILE__); WordPressUtil.download_archives!'
