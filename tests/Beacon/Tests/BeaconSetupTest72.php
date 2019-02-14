<?php
namespace Beacon\Tests;

use PHPUnit_Framework_TestCase;

class BeaconSetupTest extends PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        error_reporting(-1);
        $this->router = require(__DIR__ . '/BeaconPreset.php');
    }
}