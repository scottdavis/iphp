<?php
	require_once(dirname(__FILE__) . '/../../lib/temp_file.php');
 	class TempFileMock extends TempFile {
		public static function fileName($name) {
			return vfsStream::url('root/' . $name);
		}
	}