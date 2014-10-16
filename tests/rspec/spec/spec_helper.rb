require "capybara/rspec"
require "capybara/mechanize"
require "childprocess"

require "fileutils"
require "shellwords"
require 'ostruct'
require 'open-uri'
require 'pathname'
require 'thread'

ROOT_PATH              = File.expand_path("../../../../", __FILE__)
WPDC_PATH              = File.join(ROOT_PATH, "wpdc")
RSPEC_PATH             = File.join(ROOT_PATH, "tests", "rspec")
SPEC_PATH              = File.join(RSPEC_PATH, "spec")
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
  process = ChildProcess.build("php", "-c", "php.ini", "-d", "error_reporting=0", "-d", "display_errors=0", "-S", host_and_port, "-t", document_root)
  process.cwd = document_root
  process.start
  $child_procs << process
  sleep 0.5
  process
end

RSpec.configure do |config|

  config.before(:suite) do
    puts "* Downloading supported wordpress versions"
    WordPressUtil.download_archives!

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

class WordPressUtil

  def self.work_queue!(queue, &block)
    5.times.map do |i|
      Thread.new do
        Thread.current[:id] = i.to_s
        def puts(output); super("Worker #{Thread.current[:id]}: " + output) end
        begin
          while x = queue.pop(true) do
            block.call(x)
          end
        rescue ThreadError
          puts "Done"
        end
      end
    end.map(&:join)
  end

  def self.archives_path
    Pathname.new(File.join(SUPPORT_PATH, "wordpress_archives")).tap(&:mkpath)
  end

  def self.archives
    archives_path.children
  end

  def self.iversion(version)
    version << ".0" if(version.split('.').length == 2)
    version << ".0" if(version.split('.').length == 3)
    version.to_s.split('.').reverse.each_with_index.reduce(0) do |seed, (part, i)|
      calc = part.to_i
      calc = calc * (10 ** (i * 3)) if i > 0
      seed += calc
      seed
    end
  end

  def self.download_archives!

    # Non Multi & >= version 2
    html = open("https://wordpress.org/download/release-archive/").read
    urls = html.scan(/(http\:\/\/wordpress\.org\/wordpress\-[0-9\.]+?\.zip)["']{1}/).flatten.select do |url|
      version = url[/-([0-9\.]+?).zip/, 1]
      iversion(version) > iversion("2.0.1.0")
    end

    # Get Download URLS
    downloaded_files = archives_path.children.map(&:basename).map(&:to_path)
    queue = urls.reduce(Queue.new) do |queue, url|
      if !downloaded_files.include?(File.basename(url = url.gsub("http:", "https:")))
        queue << url
      end
      queue
    end

    # Download Archive
    if queue.length > 0
      work_queue!(queue) do |url|
        file_path = archives_path.join(File.basename(url))
        puts "* Downloading: #{url} ---> TO ---> #{file_path.to_path}"
        begin
          File.open(file_path.to_path, "wb") do |file|
            open(url, "rb") {|download| file.write(download.read) }
          end
        rescue
          file_path.delete if file_path.exists?
        end
      end
    end

  end

end

