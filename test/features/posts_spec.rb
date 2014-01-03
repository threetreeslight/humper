require 'spec_helper'

feature "posts" do
  scenario "GET /posts" do
    visit 'http://blog.local/posts'
    # Run the generator again with the --webrat flag if you want to use webrat methods/matchers
    expect(page).to have_text("posts")
  end
end
