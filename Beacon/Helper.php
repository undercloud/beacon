<?php
namespace Beacon;

class Helper
{
	public static function normalize($path)
	{
		return '/' . trim($path, '/');
	}
	
	public static function compile($path)
	{
		$regexp = '~\(?\/:[\w\)]*~';
		
		$compiler = function ($e) {
			$param = $e[0];
			$compiled = '/[\w]*';
			
			if ($param[0] === '(' and substr($param, -1) === ')') {
				$compiled = '(' . $compiled . ')?';
			}
			
			return $compiled;
			
		};
	
		return preg_replace_callback($regexp, $compiler, $path);
	}
	
	public static function extractPlaceholder($path)
	{
		$segments = explode('/', $path);

		$segments = array_filter($segments, function ($item) {
			return (isset($item[0]) and $item[0] === ':');
		});

		$segments = array_map(function ($item) {
			return preg_replace('~\W~', '', $item);
		}, $segments);
		
		$segments = array_flip($segments);

		return $segments;
	}
	
	public static function fetchPlaceholder($placeholder, $path)
	{
		$segments = explode('/', $path);
		
		foreach ($placeholder as $name => $index) {
			if (isset($segments[$index])) {
				$placeholder[$name] = $segments[$index];
			} else {
				$placeholder[$name] = null;
			}
		}

		return $placeholder;
	}

	public static function retrieveAction($controller, $path, $uri)
	{
		$trimmed = substr($uri, strlen($path));

		if (!$trimmed) return false;

		$action = reset(array_filter(explode('/', $trimmed)));
		$action = preg_replace('~\W~', '', $action);

		if (!$action) return false;

		if (method_exists($controller, $action)) {
			return $action;
		}

		return false;
	}
	
	public static function checkParams($params, $where)
	{
		
		return true;
	}
	
	public static function arrayify($what)
	{
		if (!is_array($what)) {
			$what = array($what);
		}
		
		return $what;
	}
	
	public static function processOptions(array $options)
	{
		$formatted = array();
		foreach ($options as $option) {
			foreach ($option as $key => $value) {
				switch ($key) {
					case 'secure':
						$formatted[$key] = $value;
					break;
					
					case 'method':
					case 'middleware':					
						if (!isset($formatted[$key])) {
							$formatted[$key] = array();
						}
						
						list($corns, $darnels) = call_user_func(function ($array) {
							$ok   = array();
							$fail = array();
							
							foreach ($array as $key => $value) {
								if (false !== strpos($value, ':')) {
									$ok[$key] = $value; 
								} else {
									$fail[$key] = $value; 
								}
							}
							
							return array($ok, $fail);
						}, $value);
						
						if ($darnels) {
							$formatted[$key] = $darnels;
						} else {
							foreach ($corns as $corn) {
								list($op, $item) = explode(':', $corn, 2);
								
								if ($op === 'add') {
									$formatted[$key][] = $item;
								} else if ($op === 'del') {
									$formatted[$key] = array_diff($formatted[$key], array($item));
								}
							}
						}
					break;
				}
			}
		}
		
		return $formatted;
	}
}
?>