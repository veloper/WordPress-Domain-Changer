require "capybara/rspec"
require "capybara/mechanize"
require "childprocess"
require "fileutils"
require "shellwords"
require "pry"

ROOT_PATH        = File.expand_path("../../../../", __FILE__)
WPDC_PATH        = File.join(ROOT_PATH, "wpdc")
INTEGRATION_PATH = File.join(ROOT_PATH, "tests", "integration")
DUMMY_PATH       = File.join(INTEGRATION_PATH, "spec", "support", "dummy")
DUMMY_WPDC_PATH  =  File.join(DUMMY_PATH, "wpdc")
DUMMY_WPDC_CONFIG_PATH = File.join(DUMMY_WPDC_PATH, "config.php")

$child_procs = {}

RSpec.configure do |config|

  config.before(:suite) do
    puts "* Copying WPDC to #{DUMMY_WPDC_PATH}"
    FileUtils.mkdir_p DUMMY_WPDC_PATH
    FileUtils.cp_r WPDC_PATH, DUMMY_PATH

    puts "* Changing password to 'test'"
    IO.write(DUMMY_WPDC_CONFIG_PATH, "<?php\ndefine('WPDC_PASSWORD', 'test');")

    puts "* Starting WPDC Web Server"
    args = [
      "php",
      "-c", "php.ini",
      "-d", "error_reporting=0",
      "-d", "display_errors=0",
      "-S", "localhost:8002",
      "-t", File.join(DUMMY_WPDC_PATH)
    ]

    process = ChildProcess.build(*args)
    process.io.stdout = process.io.stderr = out = Tempfile.new("duplex").tap{|x| x.sync=true}
    process.cwd = DUMMY_WPDC_PATH
    process.start

    sleep 0.1

    $child_procs[:wpdc_web_server] = process
  end

  config.after(:suite) do
    $child_procs.values.each(&:stop)
  end

  config.before(:suite) do
    Capybara.run_server             = false
    Capybara.app                    = true
    Capybara.app_host               = "http://localhost:8002" # Set app host
    Capybara.default_selector       = :css
    Capybara.default_wait_time      = 2
    Capybara.ignore_hidden_elements = false
    Capybara.default_driver         = :mechanize
    Capybara.javascript_driver      = :selenium
  end

  config.include Capybara::DSL

end

