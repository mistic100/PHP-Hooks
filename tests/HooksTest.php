<?php

use Hooks\Hooks;

class HooksTest extends PHPUnit_Framework_TestCase
{
  protected static $hooks;
  
  public static function setUpBeforeClass()
  {
    self::$hooks = new Hooks();
  }
  
  public function setUp()
  {
    self::$hooks->filters = array();
    self::$hooks->actions_hits = array();
    self::$hooks->filters_stack = array();
  }
  
  public function testAction()
  {
    $done = false;
    
    self::$hooks->add_action('foo', function() use(&$done) {
      $done = !$done;
    });
    
    self::$hooks->do_action('foo');
    $this->assertTrue($done);
  }
  
  public function testFilter()
  {
    $content = 'Hello world';
    
    self::$hooks->add_filter('foo', function($content) {
      return '<b>' . $content . '</b>';
    });
    
    $this->assertEquals(self::$hooks->apply_filters('foo', $content), '<b>Hello world</b>');
  }
  
  /**
   * @expectedException              \Exception
   * @expectedExceptionMessageRegExp /.* recursive nested hook detected/
   */
  public function testRecursiveException()
  {
    self::$hooks->add_action('foo', function() {
      self::$hooks->do_action('bar');
    });
    
    self::$hooks->add_action('bar', function() {
      self::$hooks->do_action('foo');
    });
    
    self::$hooks->do_action('foo');
  }
  
  public function testRemoveHandler()
  {
    $done = false;
    
    $do = function() use(&$done) {
      $done = !$done;
    };
    
    self::$hooks->add_action('foo', $do);
    self::$hooks->remove_action('foo', $do);
    
    self::$hooks->do_action('foo');
    $this->assertFalse($done);
  }
  
  public function testDisableHandler()
  {
    $done = false;
    
    $do = function() use(&$done) {
      $done = !$done;
    };
    
    self::$hooks->add_action('foo', $do);
    self::$hooks->disable_action('foo', $do);
    
    self::$hooks->do_action('foo');
    $this->assertFalse($done);
    
    self::$hooks->enable_action('foo', $do);
    
    self::$hooks->do_action('foo');
    $this->assertTrue($done);
    
    self::$hooks->disable_action('foo');
    
    self::$hooks->do_action('foo');
    $this->assertTrue($done);
    
    self::$hooks->enable_action('foo');
    
    self::$hooks->do_action('foo');
    $this->assertFalse($done);
  }
  
}