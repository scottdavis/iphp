<?php
	require_once(dirname(__FILE__) . '/iphp.php');
	
	class Tokenizer {
		/**
		* This class takes a php code string and checks its syntax and its token
		* Use this class to test input for multiLine cases
		*/
		static $multiTriggers = array(T_CLASS, T_ABSTRACT, T_INTERFACE, T_ELSE, T_ELSEIF, T_FOR, T_FOREACH, T_FUNCTION, T_PRIVATE, T_PUBLIC, T_PROTECTED, T_SWITCH, T_TRY, T_STATIC, T_CATCH);
		var $triggerNameCache = array();
		var $hasMultiLineOperator = false;
		var $phpCommand = null;
		var $tokenData = array();
		
		/**
		* @param string $syntax - PHP code string to tokenize
		*/
		public function __construct($syntax) {
			$this->syntax = $syntax;
			$this->process();
			$this->phpCommand = iphp::find_executable();
		}
		/**
		* Process the token data and extract needed codes
		* @return void
		*/
		private function process() {
			$this->tokenData = token_get_all('<?php ' . $this->syntax);
			foreach($this->tokenData as $t) {
				$this->operators[] = $t[0];
			}
		}
		/**
		* Turns the operator constant into a human readable string
		* @param integer $code - operator ID code
		* @see http://php.net/manual/en/tokens.php
		* @return string
		*/
		public function getTriggerName($code) {
			$code = (int) $code;
			$str = token_name($code);
			if(($r = strtolower($str)) == 'unknown') {
				return $r; 
			}
			return (isset($this->triggerNameCache[$code])) ? $this->triggerNameCache[$code] : $this->triggerNameCache[$code] = substr(strtolower($str), 2, strlen($str));
		}
		/**
		* Runs a syntax check on the supplied code string
		* @return boolean
		*/
		public function checkSyntax() {
			ob_start();
			$time = rand(0, time());
			$file = tempnam(sys_get_temp_dir(), "iphp.token.{$time}.");
			file_put_contents($file, '<?php ' . trim($this->syntax) . ' ?>');
			$commands = array($this->phpCommand, "-l", $file, "2>&1");
			exec(implode(" ", $commands), $out);
			unlink($file);
			ob_end_clean();
			if(strpos(implode('', $out), 'No syntax errors detected in') !== false) {
				return true;
			}
			return false;
		}
		/**
		* Detects if the supplied code string has a operator that will need multiline support
		* @return boolean
		*/
		public function isMultiLine() {
			foreach($this->operators as $op) {
				if(in_array($op, self::$multiTriggers)) {
					return true;
				}
			}
			return false;
		}
		/**
		* Returns an array of human readable operator names found by the parser
		* @return array
		*/
		public function getCleanOperatorNames() {
			return array_map(array($this, 'getTriggerName'), $this->operators);
		}

		
	}