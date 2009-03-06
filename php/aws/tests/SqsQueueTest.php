<?php
/**
 * If you want to run the unit test:
 * 
 * - Replace the two lines below with a require statement for your sqs_queue.php file
 * - Replace all App::config('aws') occurences with array('key' => ..., 'secretKey' => ...)
 * - Run 'phpunit SqsQueueTest.php' in your shell
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/app/config/bootstrap.php');

App::load('Vendor', 'sqs_queue.php');
class SqsQueueTest extends PHPUnit_Framework_TestCase {
	public $testQueue;

	public function setUp() {
		if (!defined('TEST_QUEUE')) {
			define('TEST_QUEUE', 'test_' . sha1((string)microtime(true)));
		}
		$this->testQueue = new SqsQueue(TEST_QUEUE, App::config('aws'));
	}

	public function testNotExists() {
		$this->assertFalse($this->testQueue->exists());
	}

	public function testCreate() {
		$expected = SqsQueue::url(TEST_QUEUE);
		$r = $this->testQueue->create(array('timeout' => 23));
		$this->assertEquals($expected, $r);
	}

	public function testAttributes() {
		$r = $this->testQueue->attributes();
		$this->assertArrayHasKey('VisibilityTimeout', $r);
		$this->assertArrayHasKey('ApproximateNumberOfMessages', $r);
	}

	public function testExists() {
		$this->assertTrue($this->testQueue->exists());
	}

	public function testSendMessage() {
		$this->assertType('string', $this->testQueue->sendMessage(array('Fubar')));
	}

	public function testCount() {
		$timeout = strtotime('+10 seconds');
		while (($count = count($this->testQueue)) < 1) {
			if (time() > $timeout) {
				return $this->assertTrue(false);
			}
		}
		$this->assertEquals(1, $count);
	}

	public function testReceiveMessage() {
		$timeout = strtotime('+10 seconds');
		while (!($message = $this->testQueue->receiveMessage())) {
			if (time() > $timeout) {
				return $this->assertTrue(false);
			}
		}
		$this->assertEquals(array('Fubar'), $message['body']);
	}

	public function testDeleteMessage() {
		$timeout = strtotime('+10 seconds');
		while (!($message = $this->testQueue->receiveMessage())) {
			if (time() > $timeout) {
				return $this->assertTrue(false);
			}
		}

		$this->assertTrue($this->testQueue->deleteMessage($message));
	}

	public function testNoCount() {
		$timeout = strtotime('+10 seconds');
		while (($count = count($this->testQueue)) > 0) {
			if (time() > $timeout) {
				return $this->assertTrue(false);
			}
		}
		$this->assertEquals(0, $count);
	}

	public function testAttribute() {
		$this->assertEquals(23, $this->testQueue->attribute('VisibilityTimeout'));
		$this->assertEquals(42, $this->testQueue->attribute('VisibilityTimeout', 42));
	}

	public function testListAll() {
		$expected = SqsQueue::url(TEST_QUEUE);

		$timeout = strtotime('+60 seconds');
		while (!in_array($expected, SqsQueue::listAll(App::config('aws')))) {
			if (time() > $timeout) {
				return $this->assertTrue(false);
			}
		}
		$this->assertTrue(true);
	}

	public function testDelete() {
		$this->assertTrue($this->testQueue->delete());
	}
}

?>