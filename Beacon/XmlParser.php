<?php
namespace Beacon;

use Beacon\Router;
use SimpleXMLElement;
use Beacon\ClosureQueue;
use Beacon\RouteException;

class XmlParser
{
	private $router;

	public function __construct(Router $router = null)
	{
		$this->router = $router;
	}

	public function parseOptions(SimpleXMLElement $node)
	{
		if (!$node->count()) return [];

		$options = [];
		foreach ($node[0] as $e) {
			$key = $e->getName();
			$value = null;

			switch ($key) {
				case 'secure':
					$value = (
						'true' === (string)$e->attributes()->value
						? true : false
					);
				break;

				case 'method':
				case 'middleware':
					$value = explode(',', (string)$e->attributes()->value);
				break;

				case 'where':
					$where = [];
					foreach ($e->children() as $var) {
						$varName = $var->getName();

						$where[$varName] = [
							'regexp' => (string)$var->attributes()->regexp
						];

						if (isset($var->attributes()->default)) {
							$where[$varName]['default'] = (string)$var->attributes()->default;
						}
					}

					$value = $where;
				break;
			}

			$options[$key] = $value;
		}

		return $options;
	}

	public function parseRoute(SimpleXMLElement $node)
	{
		$method = $node->getName();

		$attributes = $node->attributes();
		$path = (string)$attributes['path'];
		$call = (string)$attributes['call'];

		$options = $this->parseOptions($node->options);

		return (new ClosureQueue($this->router))->wrap($method, array($path, $call, $options));
	}

	public function parseGroup(SimpleXMLElement $group, $domain = false)
	{
		$method = 'group';

		$attributes = $group->attributes();
		$prefix = (string)$attributes['prefix'];
		$options = [];

		$queue = new ClosureQueue($this->router);
		foreach ($group->children() as $node) {
			if ('group' === $node->getName()) {
				$queue->enqueue($this->parseGroup($node));
			} else if ('options' === $node->getName()) {
				if (!$domain) {
					$options = $this->parseOptions($node);
				}
			} else {
				$queue->enqueue($this->parseRoute($node));
			}
		}

		$call = $queue->getClosure();

		return (new ClosureQueue($this->router))->wrap($method, array($prefix, $call, $options));
	}

	public function parseDomain(SimpleXMLElement $domain)
	{
		$method = 'domain';

		$attributes = $domain->attributes();
		$host = (string)$attributes['host'];

		$call = $this->parseGroup($domain, true);
		$options = $this->parseOptions($domain->options);

		return (new ClosureQueue($this->router))->wrap($method, array($host, $call, $options));
	}

	public function parse($path)
	{
		libxml_clear_errors();
		$sxl = @simplexml_load_file($path);

		if (false === $sxl) {
			$lastError = libxml_get_last_error();

			throw new RouteException(
				sprintf(
					'Error while parsing %s:%s:%s with message: %s',
					$lastError->file,
					$lastError->line,
					$lastError->column,
					$lastError->message
				)
			);
		}

		if(isset($sxl->route)){
			$queue = new ClosureQueue($this->router);

			$routes = $sxl->route->children();
			foreach($routes as $route){
				$tag = $route->getName();
				switch ($tag) {
					default:
						$queue->enqueue($this->parseRoute($route));
					break;

					case 'options':
						$queue->enqueue(
							(new ClosureQueue($this->router))
								->wrap('globals', [$this->parseOptions($route)])
						);
					break;

					case 'group':
						$queue->enqueue($this->parseGroup($route));
					break;

					case 'domain':
						$queue->enqueue($this->parseDomain($route));
					break;
				}
			}

			$queue();
		}
	}
}
?>