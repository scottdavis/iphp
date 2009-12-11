<?php
require_once ('PHPUnit/Framework.php');
require_once ('vfsStream/vfsStream.php');
require_once (dirname(__FILE__) . '/../lib/temp_file.php');
class TempFileTest extends PHPUnit_Framework_TestCase {

    public function testWritesToTempFile() {
        $data = 'WHOA! im Data';
        $file = TempFile::fileName('test');
        TempFile::writeToFile('test', $data);
        $this->assertEquals($data, file_get_contents($file));
    }
    public function testReadFromTempFile() {
        $data = 'WHOA! im Data';
        $file = TempFile::fileName('test');
        file_put_contents($file, $data);
        $this->assertEquals($data, TempFile::readFromFile('test'));
    }
    public function testReset() {
        $data = 'WHOA! im Data';
        TempFile::writeToFile('test', $data);
        $this->assertEquals($data, TempFile::readFromFile('test'));
        TempFile::reset('test');
        $this->assertEquals('', TempFile::readFromFile('test'));
    }
    public function testClear() {
        $files = array('test1', 'test2');
				$_files = array();
        foreach($files as $file) {
           $_files[] = TempFile::fileName($file);
        }
        TempFile::clear();
        foreach($_files as $file) {
            $this->assertFalse(file_exists($file));
        }
    }
}
