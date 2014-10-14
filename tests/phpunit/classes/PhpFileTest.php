<?php
require_once dirname(__FILE__) . '/../unit_helper.php';
require_once CLASSES_PATH . '/class.PhpFile.php';

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
    $this->assertEquals("wordpress_test_", $this->file->getVariable("table_prefix"));
  }

  public function testGetConstant()
  {
    $array = array(
      'DB_NAME'     => 'wordpress_test',
      'DB_USER'     => 'wordpress_test',
      'DB_PASSWORD' => 'wordpress_test',
      'DB_HOST'     => 'localhost:8889',
      'DB_CHARSET'  => 'db_utf8'
    );
    foreach($array as $key => $value) {
        $this->assertEquals($value, $this->file->getConstant($key));
    }
  }
}