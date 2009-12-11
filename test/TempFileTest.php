<?php
require_once ('PHPUnit/Framework.php');
require_once ('vfsStream/vfsStream.php');
require_once (dirname(__FILE__) . '/mocks/temp_file_mock.php');
class TempFileTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        vfsStreamWrapper::register();
        $root = new vfsStreamDirectory('root');
        vfsStreamWrapper::setRoot($root);
    }
    public function testReturnsFileName() {
        $this->assertEquals(vfsStream::url('root/test'), TempFileMock::fileName('test'));
    }
    public function testWritesToTempFile() {
        $data = 'WHOA! im Data';
        $file = TempFileMock::fileName('test');
        TempFileMock::writeToFile('test', $data);
        $this->assertEquals($data, file_get_contents($file));
    }
    public function testReadFromTempFile() {
        $data = 'WHOA! im Data';
        $file = TempFileMock::fileName('test');
        file_put_contents($file, $data);
        $this->assertEquals($data, TempFileMock::readFromFile('test'));
    }
    public function testReset() {
        $data = 'WHOA! im Data';
        TempFileMock::writeToFile('test', $data);
        $this->assertEquals($data, TempFileMock::readFromFile('test'));
        TempFileMock::reset('test');
        $this->assertEquals('', TempFileMock::readFromFile('test'));
    }
    public function testClear() {
        $files = array('test1', 'test2');
        foreach($files as $file) {
            TempFileMock::fileName($file);
        }
        TempFileMock::clear();
        foreach($files as $file) {
            $this->assertFalse(file_exists($file));
        }
    }
}
