<?php
require_once 'wpdc/classes/class.PhpFile.php';

class PhpFileTest extends PHPUnit_Framework_TestCase
{

  protected $file = null;

  public function setUp()
  {
    $this->file = new PhpFile(realpath(dirname(__FILE__) . '/../support/wp-config.php'));
  }

  public function testLoadingAFile()
  {
    $this->file->load();
    $this->assertNotEmpty($this->file);
  }

  public function testGetVariable()
  {
    $this->assertEquals("test_wp_", $this->file->getVariable("table_prefix"));
  }

  public function testGetConstant()
  {
    $array = array(
      'DB_NAME'     => 'test_db_name',
      'DB_USER'     => 'test_db_user',
      'DB_PASSWORD' => 'test_db_pass',
      'DB_HOST'     => 'test_db_host',
      'DB_CHARSET'  => 'test_db_utf8'
    );
    foreach($array as $key => $value) {
        $this->assertEquals($value, $this->file->getConstant($key));
    }
  }
}