require 'capybara'
require "capybara/rspec"
require 'capybara/poltergeist'

Capybara.default_driver = :poltergeist

Capybara.configure do |config|
  config.match = :one
  config.exact_options = true
  config.ignore_hidden_elements = true
  config.visible_text_only = true
end
