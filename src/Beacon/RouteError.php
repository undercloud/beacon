<?php
namespace Beacon;

/**
 * RouteError
 *
 * @category Router
 * @package  Beacon
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/beacon
 */
class RouteError
{
    const NO_ERROR                 = 0;
    const NOT_FOUND_ERROR          = 1;
    const SECURE_ERROR             = 2;
    const CONTROLLER_RESOLVE_ERROR = 4;
    const WHERE_REGEX_ERROR        = 8;
    const REST_RESOLVE_ERROR       = 16;
    const AUTH_ERROR               = 32;

    /**
     * @var integer
     */
    protected static $code = 0;

    /**
     * Set error code
     *
     * @param int $code value
     *
     * @return void
     */
    public static function setErrorCode($code)
    {
        static::$code = $code;
    }

    /**
     * Get error code
     *
     * @return int
     */
    public static function getErrorCode()
    {
        return static::$code;
    }
}
