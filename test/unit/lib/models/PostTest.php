<?php
require_once '/var/www/test/test_helper.php';

class PostTest extends PHPUnit2_Framework_TestCase {

    private $member_id = "0";
    private $post     = "1000000";
    private $alread_post_num;
    private $post_;

    /**
    * PHPUnite2 constractor
    */
    public function PostTest($name) {
        parent::__construct($name);
    }

    /**
     * Initialize Test:
     *   any test do setUp, tearDown process.
     *   if you need some initialize before test (like creating db connect),
     *   write here.
     */
    public function setUp() {
        $this->alread_post_num = count(Post::findAll());

        $this->post_ = new Post();
        $this->post_->member_id = $this->member_id;
        $this->post_->post     = $this->post;
        $this->post_->save();
    }

    /**
     * finalize test:
     *   any test do setUp, tearDown process.
     *   if you need some initialize after test (like closing db connect),
     *   write here.
     */
    public function tearDown() {
        $this->post_->destroy();
    }

    /**
     * test prefix method has been test
     */

    // create() method
    public function testCreate() {
        // record create by setUp()
        $this->assertEquals(
            $this->post_->id,
            Post::findBy(array(':member_id' => $this->member_id))->post
        );
    }

    // ::where method
    public function testWhereByArray() {
        $post = Post::where(array(':id' => $this->post_->id));
        $this->assertEquals($this->post_->id, $post[0]->id );
    }
    public function testWhereByArrayWithSelect() {
        $post = Post::where(array(':id' => $this->post_->id), array(), array('id as foo'));
        $this->assertEquals($this->post_->id, $post[0]->foo );
    }
    public function testWhereByString() {
        $post = Post::where('id = ?', array($this->post_->id));
        $this->assertEquals($this->post_->id, $post[0]->id );
    }
    public function testWhereByStringWithSelect() {
        $post = Post::where('id = ?', array($this->post_->id), array('id as foo'));
        $this->assertEquals($this->post_->id, $post[0]->foo );
    }



    // ::find method
    public function testFind() {
        $this->assertEquals($this->post_->id, Post::find($this->post_->id)->id );
    }

    // ::findAll method
    public function testFindAll() {
        $this->assertEquals($this->alread_post_num + 1, count(Post::findAll()));
    }

    // ::findBy
    public function testFindBy() {
        $this->assertEquals(
            $this->member_id,
            Post::findBy(array(':member_id' => $this->member_id))->member_id
        );
    }

    // save() method
    public function testUpdate() {
        $update_post = '20000';

        $this->post_->post = $update_post;
        $this->post_->save();

        $this->assertEquals($update_post, Post::find($this->post_->id)->post);
    }

    // ::isUniqueMemberId positive test
    public function testIsUniqueMemberIdPositive() {
        $result = Post::isUniqueMemberId('999999999');
        $this->assertEquals(true, $result);
    }
    // ::isUniqueMemberId negative test
    public function testIsUniqueMemberIdNegative() {
        $result = Post::isUniqueMemberId($this->member_id);
        $this->assertEquals(false, $result);
    }

    // destroy()
    public function testDestroy() {
        $this->post_->destroy();
        $this->assertEquals(false, Post::find($this->post_->id));
    }
}
