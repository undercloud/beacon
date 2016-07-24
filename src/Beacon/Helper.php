<?php
namespace Beacon;

use Beacon\Route;

class Helper
{
	public function noop()
	{
		return function () {};
	}

	public static function arrayify($what)
	{
		if (!is_array($what)) {
			$what = [$what];
		}

		return $what;
	}

	public static function normalize($path)
	{
		if ('/' === $path) return $path;

		return rtrim($path, '/');
	}

	public static function compile($path)
	{
		$regexp = '~\(?\/:[\w\)]*~';

		$compiler = function ($e) {
			$param = $e[0];
			$compiled = '/[\w]+';

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

	public static function fetchPlaceholder(Route $route, $path)
	{
		$params = $route->getParams();
		$segments = explode('/', $path);

		foreach ($params as $name => $index) {
			if (isset($segments[$index])) {
				$params[$name] = $segments[$index];
			} else {
				$params[$name] = null;
			}
		}

		$route->setParams($params);
	}

	public static function processOptions(array $options)
	{
		$formatted = array();
		foreach ($options as $option) {
			foreach ($option as $key => $value) {
				switch ($key) {
					case 'secure':
					case 'where':
						$formatted[$key] = $value;
					break;

					case 'method':
					case 'middleware':
						if (!isset($formatted[$key])) {
							$formatted[$key] = array();
						}

						$value = static::arrayify($value);

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

						$formatted[$key] = array_values($formatted[$key]);
					break;
				}
			}
		}

		return $formatted;
	}
}
?>