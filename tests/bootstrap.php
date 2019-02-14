<?php
    // backward compatibility
    if (!class_exists('\PHPUnit\Framework\TestCase', true)) {
        class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
    } elseif (!class_exists('\PHPUnit_Framework_TestCase', true)) {
        class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
    }

    if (defined('HHVM_VERSION') or version_compare(PHP_VERSION, '7.2.0') >= 0) {
        require_once __DIR__ . '/Beacon/Tests/BeaconSetup72.php';
    } else {
        require_once __DIR__ . '/Beacon/Tests/BeaconSetup.php';
    }

	require_once __DIR__ . '/../src/Beacon/Beacon.php';
?>
