<?php
namespace Beacon;

use Exception;

/**
 * RouteException
 *
 * @category Router
 * @package  Beacon
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/beacon
 */
class RouteException extends Exception
{
    /**
     * Initialize
     *
     * @param string         $message  error
     * @param integer        $code     error
     * @param Exception|null $previous instance
     */
	public function __construct($message, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}