require 'spec_helper'

describe 'Login Page' do
  subject { page }

  context "when visiting the root path" do
    before { visit '/' }
    its(:current_path) { should == '/index.php/login' }
  end

  context "when the default password has not changed" do
    before(:each) do
      IO.write(DUMMY_WPDC_CONFIG_PATH, IO.read(DUMMY_WPDC_CONFIG_PATH).gsub("test", "Replace-This-Password"))
      visit '/'
    end
    after(:each) { IO.write(DUMMY_WPDC_CONFIG_PATH, IO.read(DUMMY_WPDC_CONFIG_PATH).gsub("Replace-This-Password", "test")) }

    it { should have_css '#flash .warning' }
  end

  context "Logging in" do
    before do
      visit '/'
      fill_in 'password', :with => password
      click_button 'Login'
    end

    context "when WPDC_PASSWORD has been changed" do
      context "using a valid password" do
        let(:password) { "test" }
        it { should have_css '#flash .success' }
      end

      context "using a invalid password" do
        let(:password) { "invalid" }
        it { should have_css '#flash .error' }
      end

    end

  end

end
