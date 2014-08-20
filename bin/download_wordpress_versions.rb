require 'open-uri'
require 'pathname'
require 'thread'

def work_queue!(queue, &block)
  5.times.map do |i|
    Thread.new do
      Thread.current[:id] = i.to_s
      def puts(output)
        super("Worker #{Thread.current[:id]}: " + output)
      end

      begin
        while x = queue.pop(true)
          block.call(x)
        end
      rescue ThreadError
        puts "I'm dead!"
      end
    end
  end.map(&:join)
end

DOWNLOADS_PATH = Pathname.new(File.expand_path('../../tmp/downloads', __FILE__)).tap(&:mkpath)
DUMMIES_PATH = Pathname.new(File.expand_path('../../tmp/dummies', __FILE__)).tap(&:mkpath)

# Non MU, >= version 2
html = open("http://wordpress.org/download/release-archive").read
urls = html.scan(/(http\:\/\/wordpress\.org\/wordpress\-[0-9\.]+?\.zip)["']{1}/).flatten.select{|url| url[/([0-9]{1})/,1].to_i >= 2}

# Download Wordpress Versions
downloaded_files = DOWNLOADS_PATH.children.map(&:basename).map(&:to_s)
download_queue = urls.reduce(Queue.new) do |queue, url|
  queue << url unless downloaded_files.include? File.basename(url)
  queue
end

work_queue!(download_queue) do |url|
  write_file = DOWNLOADS_PATH.join(File.basename(url))
  puts "* Downloading: #{url} ---> TO ---> #{write_file.to_path}"
  File.open(write_file.to_path, "wb") do |saved_file|
    open(url, "rb") do |read_file|
      saved_file.write(read_file.read)
    end
  end
end

archive_to_destination_queue = DOWNLOADS_PATH.children.to_a.each.reduce(Queue.new) do |queue, child|
  if child.to_path[".zip"]
    queue << [child.to_path, DUMMIES_PATH.join(child.basename.to_s.gsub(".zip", "")).to_path]
  end
  queue
end

work_queue!(archive_to_destination_queue) do |archive_to_destination|
  archive, destination = archive_to_destination
  puts "* Unzip: #{archive} ---> TO ---> #{destination}"
  `unzip -o #{archive} -d #{destination}`
end
















