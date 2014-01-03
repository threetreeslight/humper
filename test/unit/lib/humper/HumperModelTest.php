<?php
require_once '/var/www/test/test_helper.php';

class HumperModelTest extends PHPUnit2_Framework_TestCase {

    private $humper_model_;

    /**
    * PHPUnite2 constractor
    */
    public function PointTest($name) {
        parent::__construct($name);
    }

    /**
     * Initialize Test:
     *   any test do setUp, tearDown process.
     *   if you need some initialize before test (like creating db connect),
     *   write here.
     */
    public function setUp() {
        HumperDbConfig::setLoggingMode(false);
        $this->humper_model_ = new HumperModel();
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
    public function testSetLoggerPositive() {
        HumperDbConfig::setLoggingMode(true, constant("LOG_DIR"));
        $this->humper_model_ = new HumperModel();

        $this->assertEquals( 'Log_file', get_class($this->humper_model_->getLogger()) );
    }
}
