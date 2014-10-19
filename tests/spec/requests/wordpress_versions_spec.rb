require_relative '../spec_helper.rb'

WordPressUtil.archives.each do |wp_archive_path|
  meta = {
    :wp => {
      :archive_path => wp_archive_path,
      :install_path => Pathname.new("#{DUMMY_PATH}/wordpress"),
      :version      => wp_archive_path.basename.to_s[/([0-9\.]+)\.zip/, 1],
      :username     => 'admin',
      :password     => 'aTestPass0rd!',
      :email        => 'test@example.com',
      :title        => 'my test blog',
      :prefix       => 'wp_test_',
    },
    :db => {
      :host => MYSQL[:host],
      :port => MYSQL[:port],
      :user => MYSQL[:user],
      :name => "wordpress"
    },
    :wpdc => {
      :dummy_path => Pathname.new(DUMMY_WPDC_PATH),
      :password => "test",
      :old_domain => "localhost:8005",
      :new_domain => "localhost:8006"
    }
  }

  describe "Change Domain of WordPress #{meta[:wp][:version]}", meta do

    let(:wp)   { |e| OpenStruct.new e.metadata[:wp] }
    let(:db)   { |e| OpenStruct.new e.metadata[:db] }
    let(:wpdc) { |e| OpenStruct.new e.metadata[:wpdc] }

    it "Drop WordPress database" do
      output = `mysql --host #{db.host} --port #{db.port} --user #{db.user} -e "DROP DATABASE IF EXISTS #{db.name};"`
      expect($?).to be_success
    end

    it "Create WordPress database" do
      output = `mysql --host #{db.host} --port #{db.port} --user #{db.user} -e "CREATE DATABASE #{db.name};"`
      expect($?).to be_success
    end

    it "Unzip WordPress archive" do
      if wp.install_path.exist?
        wp.install_path.rmtree
      end
      `unzip -o #{wp.archive_path} -d #{DUMMY_PATH}`
      expect($?).to be_success
    end

    it "Start a PHP web server for the OLD domain" do
      php_web_server(wpdc.old_domain, wp.install_path.to_path)
      Capybara.app_host = "http://" + wpdc.old_domain
    end

    it "Run through WordPress configuration setup script" do
      visit "/wp-admin/setup-config.php?step=1"
      fill_in "dbname", :with => db.name
      fill_in "uname",  :with => db.user
      fill_in "pwd",    :with => ""
      fill_in "dbhost", :with => "#{db.host}:#{db.port}"
      fill_in "prefix", :with => wp.prefix
      click_button "Submit"
      expect(page).to have_content "sparky"
    end

    it "Run through WordPress installation script" do
      visit '/wp-admin/install.php?step=1'
      fill_in 'weblog_title', :with => wp.title
      fill_in 'admin_email', :with => wp.email

      if page.body[/<input.+?name=("|')user_name/]
        fill_in "user_name", with: wp.username
      end

      if (password_field_ids = page.body.scan(/(<input.+?type=["']password[^>]+)/).join.scan(/id=["'](.+?)["']/).flatten).any?
        password_field_ids.each {|id| fill_in id, :with => wp.password }
      end

      click_button(page.body["Continue to"] ? 'Continue' : 'Install')

      expect(page.current_url).to match(/step=2/)
    end

    it "Ensure WordPress site can be reach at OLD domain" do
      visit '/'
      expect(page.current_url).to match wpdc.old_domain
      expect(page).to have_content wp.title
    end

    it "Ensure WordPress site only references the OLD domain" do
      visit '/'
      expect(page.body.scan(wpdc.new_domain).length).to be <= 0
      expect(page.body.scan(wpdc.old_domain).length).to be > 0
    end

    it "Install WordPress-Domain-Changer into the root directory of the WordPress site" do
      FileUtils.cp_r(wpdc.dummy_path, wp.install_path)
    end

    it "Change the URL of the WordPress site using WordPress-Domain-Changer" do

      # Login
      visit '/wpdc/index.php'
      fill_in 'password', :with => wpdc.password
      click_button "Login"
      expect(page).to have_content "You have logged-in successully!"

      # Database
      expect(page).to have_content db.host
      expect(page).to have_content db.port
      expect(page).to have_content db.user
      expect(page).to have_content wp.prefix
      click_button "Next"
      expect(page).to have_content "Database connection successful!"

      # Table Selections
      all("input[type='checkbox']").each do |element|
        check element[:name]
      end
      click_button "Save"
      expect(page).to have_content "Table selections updated successully!"

      # Change Domain : Find
      fill_in "old_url", :with => wpdc.old_domain
      fill_in "new_url", :with => wpdc.new_domain
      click_button "Find"
      expect(page.all('table tbody tr').count).to be > 0

      # Change Domain : Confirm & Apply
      click_button "Apply"
      if page.driver.browser.respond_to? :switch_to
        page.driver.browser.switch_to.alert.accept
      end
      expect(page).to have_content "All database queries executed successully!"
    end

    it "Start a different PHP web server for the NEW domain" do
      php_web_server(wpdc.new_domain, wp.install_path.to_path)
      Capybara.app_host = "http://" + wpdc.new_domain
    end


    it "Ensure the WordPress site can be reached at the NEW domain" do
      visit '/'
      expect(page.current_url).to match wpdc.new_domain
      expect(page).to have_content wp.title
    end

    it "Ensure the WordPress site only references the NEW domain" do
      visit '/'
      expect(page.body.scan(wpdc.old_domain).length).to be <= 0
      expect(page.body.scan(wpdc.new_domain).length).to be > 0
    end

  end

end