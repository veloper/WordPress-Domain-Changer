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
    archives_path.children.sort_by{|x| iversion(x.basename.to_s[/-([0-9\.]+?).zip/, 1]) }
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