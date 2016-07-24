<?php
namespace Beacon;

class RouteError
{
	const NO_ERROR                 = 0;
	const NOT_FOUND_ERROR          = 1;
	const SECURE_ERROR             = 2;
	const HTTP_METHOD_ERROR        = 4;
	const CONTROLLER_RESOLVE_ERROR = 8;
	const WHERE_REGEX_ERROR        = 16;

	protected static $code = 0;

	public static function setErrorCode($code)
	{
		static::$code = $code;
	}

	public static function getErrorCode()
	{
		return static::$code;
	}
}
?>