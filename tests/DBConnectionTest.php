<?php namespace Aws\Laravel\Test;

use Aws\Laravel\AwsFacade as AWS;
use Aws\Laravel\AwsServiceProvider;
use Illuminate\Container\Container;

abstract class DBConnectionTest extends \PHPUnit_Framework_TestCase
{

    public function testIsConnected()
    {
		//...
		//$this->assertEquals(true, $db->IsConnected());
    }
}
