<?php
namespace Beacon\Tests;

use PHPUnit_Framework_TestCase;

class BeaconSetup extends PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp(): void
    {
        $this->router = require(__DIR__ . '/BeaconPreset.php');
    }
}