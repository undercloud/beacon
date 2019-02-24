<?php
namespace Beacon\Tests;

use PHPUnit_Framework_TestCase;

class BeaconSetup extends PHPUnit_Framework_TestCase
{
    protected $router;

    public function setUp()
    {
        $this->router = require(__DIR__ . '/BeaconPreset.php');
    }
}