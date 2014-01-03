# A sample Guardfile
# More info at https://github.com/guard/guard#readme

require 'tapp'
require 'pry'

guard :phpunit2, tests_path: 'test/unit', all_on_start: false, notification: false do
  # Watch tests files
  watch(%r{.+/(.+Test).php$}) { |m| "#{m[1]} #{m[0]}" }

  # Watch library files and run their tests
  watch(%r{.+/(.+).php$}) { |m| "#{m[1]}Test test/unit/#{m[0].gsub(%r{\.php}, "Test.php")}" }
end


# Guard::Compass
guard :compass, configuration_file: 'config/compass.rb' do
  watch(%r{^app/assets\/sass\/(.*)\.sass})
end

# Guard::CoffeeScript
guard :coffeescript, bare: true, input: 'app/assets/coffeescripts', output: 'app/assets/javascripts' do
  watch(%r{^app/assets/coffeescripts/front/(.*).js})
end

