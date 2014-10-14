<?php
require_once '../unit_helper.php';
require_once CLASSES_PATH . '/class.BaseController.php';

class BaseControllerTest extends PHPUnit_Framework_TestCase
{

    public function setUp() {
        $this->subject = new TestController();
    }

    public function test_getRoutesWhereReturnsCorrectCounts() {
        $results = $this->subject->getRoutesWhere( array( "method" => "login" ) );
        $this->assertEquals( count( $results ), 1 );
    }
    public function test_getRoutesWhereReturnsCorrectResults() {
        $result = $this->subject->getRoutesWhere( array( "method" => "database" ) )[0];
        $expected = $this->subject->_routes[2];
        $this->assertEquals( $result, $expected );
    }

    public function test_getRouteWhereReturnsCorrectResults() {
        $result = $this->subject->getRouteWhere( array( "method" => "database" ) );
        $expected = $this->subject->_routes[2];
        $this->assertEquals( $result, $expected );
    }
}

class TestController extends BaseController {
    public function routes() {
        $this->addRoute( "GET" , "login"          , "login"         , array( "root" => true ) );
        $this->addRoute( "POST", "login/submit"   , "loginSubmit" );
        $this->addRoute( "GET" , "database"       , "database"      , array( "auth" => false ) );
        $this->addRoute( "POST", "database/submit", "databaseSubmit", array( "auth" => true ) );
        $this->addRoute( "GET" , "changer"        , "changer"       , array( "auth" => true ) );
        $this->addRoute( "POST", "changer/submit" , "changerSubmit" , array( "auth" => true ) );
        $this->addRoute( "GET" , "changer/success", "changerSuccess", array( "auth" => true ) );
    }
}
