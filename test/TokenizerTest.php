<?php
	require_once('PHPUnit/Framework.php');
	require_once(dirname(__FILE__) . '/../lib/Tokenizer.php');
 	class TokenizerTest extends PHPUnit_Framework_TestCase {
		
		public function setUp() {
			$this->klass = new Tokenizer('$foo = "bar";');
		}
		
		public function opData() {
			return array(
										array(T_FOREACH, 'foreach'),
										array(T_IF, 'if'),
										array(T_ELSE, 'else'),
										array(T_CLASS, 'class')
									);
		}
		
		/**
			* @dataProvider opData
			*/
		public function testGetOperatorName($trigger, $name) {
			$this->assertEquals($this->klass->getOperatorName($trigger), $name);
		}
		
		
		
		public function syntaxCheckData() {
			return array(
										array('$test = "foo";', true),
										array('test', true),
										array('foreach(', false),
										array('foreach(range(0,10) as $r){ echo $r;}', true),
										array('class foo }', false),
										array(substr(file_get_contents(__FILE__), 5, 0), true)
									);
		}
		
		
		/**
		 * @dataProvider syntaxCheckData
		 */
		public function testCheckSyntax($code, $pass) {
			$klass = new Tokenizer($code);
			$this->assertEquals($pass, $klass->checkSyntax());
		}
		
		
  	public function testCheckFindForeach() {
			$klass = new Tokenizer('foreach');
			$this->assertTrue($klass->isMultiLine());
		}
		
		
		public function testCheckVariableNotMultiline() {
			$klass = new Tokenizer('$test = "foo";');
			$this->assertFalse($klass->isMultiLine());
		}
		
		
	}