<?php
require_once ('PHPUnit/Framework.php');
require_once (dirname(__FILE__) . '/../iphp.php');
class IphpTest extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		$this->shell = new iphp();
	}
	
	
	public function testInitialize() {
		$this->shell->initialize();
		$this->assertEquals(6, count($this->shell->options));
	}
	
}