<?php
require_once '/var/www/test/test_helper.php';

class HumperDbConfigTest extends PHPUnit2_Framework_TestCase {

    /**
    * PHPUnite2 constractor
    */
    public function HumperDbConfigTest($name) {
        parent::__construct($name);
    }

    /**
     * Initialize Test:
     *   any test do setUp, tearDown process.
     *   if you need some initialize before test (like creating db connect),
     *   write here.
     */
    public function setUp() {
    }

    /**
     * finalize test:
     *   any test do setUp, tearDown process.
     *   if you need some initialize after test (like closing db connect),
     *   write here.
     */
    public function tearDown() {
    }

    /**
     * test prefix method has been test
     */

    // ::SetLoggingMode
    public function testSetLoggingModeNegative() {
        HumperDbConfig::setLoggingMode(false);
        $this->assertEquals( false, HumperDbConfig::getLoggingMode());
    }
    public function testSetLoggingModePositive() {
        HumperDbConfig::setLoggingMode(true, constant("LOG_DIR"));
        $this->assertEquals(
            'Log_file', get_class(HumperDbConfig::getLogger()));
    }


}
