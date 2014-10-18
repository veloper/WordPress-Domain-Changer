require "capybara/rspec"
require "capybara/mechanize"
require "childprocess"

require "fileutils"
require "shellwords"
require 'ostruct'
require 'open-uri'
require 'pathname'
require 'thread'

require File.expand_path '../support/word_press_util.rb', __FILE__

ROOT_PATH              = File.expand_path("../../", __FILE__)
WPDC_PATH              = File.join(ROOT_PATH, "wpdc")
SPEC_PATH              = File.join(ROOT_PATH, "spec")
SUPPORT_PATH           = File.join(SPEC_PATH, "support")
DUMMY_PATH             = File.join(SUPPORT_PATH, "dummy")
DUMMY_WPDC_PATH        = File.join(DUMMY_PATH, "wpdc")
DUMMY_WPDC_CONFIG_PATH = File.join(DUMMY_WPDC_PATH, "config.php")

MYSQL = {
  :host => (ENV["MYSQL_HOST"] || '127.0.0.1'),
  :port => (ENV["MYSQL_PORT"] || 8889),
  :user => (ENV["MYSQL_USER"] || 'wpdc_test')
}

$child_procs = []

def php_web_server(host_and_port, document_root)
  process = ChildProcess.build("php", "-c", "php.ini", "-d", "error_reporting=0", "-d", "display_errors=0", "-S", host_and_port.to_s, "-t", document_root.to_s)
  process.io.inherit!
  process.cwd = document_root
  process.start
  $child_procs << process
  sleep 0.5
  process
end

RSpec.configure do |config|

  config.before(:suite) do
    puts "* Copying WPDC to #{DUMMY_WPDC_PATH}"
    FileUtils.mkdir_p DUMMY_WPDC_PATH
    FileUtils.cp_r WPDC_PATH, DUMMY_PATH

    puts "* Changing WPDC password to 'test'"
    IO.write(DUMMY_WPDC_CONFIG_PATH, "<?php\ndefine('WPDC_PASSWORD', 'test');")
  end

  config.after(:suite) do
    $child_procs.each(&:stop)
  end

  config.before(:suite) do
    Capybara.run_server             = false
    Capybara.app                    = true
    Capybara.app_host               = "http://localhost:8002" # Set app host
    Capybara.default_selector       = :css
    Capybara.default_wait_time      = 1
    Capybara.ignore_hidden_elements = false
    Capybara.default_driver         = :selenium
    Capybara.javascript_driver      = :selenium
  end

  config.include Capybara::DSL

end

