<?php

namespace Vhrb\ElasticSearch\Tracy;

use Nette;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\IBarPanel;
use Vhrb\ElasticSearch\Connection;


if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}

if (!class_exists('Tracy\Bar')) {
	class_alias('Nette\Diagnostics\Bar', 'Tracy\Bar');
	class_alias('Nette\Diagnostics\BlueScreen', 'Tracy\BlueScreen');
	class_alias('Nette\Diagnostics\Helpers', 'Tracy\Helpers');
	class_alias('Nette\Diagnostics\IBarPanel', 'Tracy\IBarPanel');
}

if (!class_exists('Tracy\Dumper')) {
	class_alias('Nette\Diagnostics\Dumper', 'Tracy\Dumper');
}

class Panel extends Nette\Object implements IBarPanel
{
	/** @var null|Panel */
	private static $panel = NULL;

	/**
	 * @var float
	 */
	public $totalTime = 0;

	/**
	 * @var int
	 */
	public $queriesCount = 0;

	/**
	 * @var array
	 */
	public $queries = [];

	/** @var Connection */
	private $connection;

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		$img = Html::el('img', ['height' => '16px'])
			->src('data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/logo.png')));
		$tab = Html::el('span')->title('ElasticSearch')->add($img);
		$title = Html::el()->setText('ElasticSearch');

		if (self::$panel->queriesCount) {
			$title->setText(
				self::$panel->queriesCount . ' call' . (self::$panel->queriesCount > 1 ? 's' : '') .
				' / ' . sprintf('%0.2f', self::$panel->totalTime * 1000) . ' ms'
			);
		}

		return (string)$tab->add($title);
	}

	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (!self::$panel->queries) {
			return NULL;
		}

		ob_start();
		$esc = callback('Nette\Templating\Helpers::escapeHtml');
		$click = class_exists('\Tracy\Dumper')
			? function ($o, $c = FALSE, $d = 4) {
				return \Tracy\Dumper::toHtml($o, ['collapse' => $c, 'depth' => $d]);
			}
			: callback('\Tracy\Helpers::clickableDump');
		$totalTime = self::$panel->totalTime ? sprintf('%0.3f', self::$panel->totalTime * 1000) . ' ms' : 'none';
		$extractData = function ($object) {
			try {
				return Json::decode($object, Json::FORCE_ARRAY);

			}
			catch (Nette\Utils\JsonException $e) {
				return [];
			}
		};

		$processedQueries = [];
		$queries = self::$panel->queries;
		foreach ($queries as $i => $item) {
			$explode = explode('/', $item->fullURI);
			$host = $explode[2];
			$processedQueries[$host][$i] = $item;

			if (isset($item->exception)) {
				continue; // exception, do not re-execute
			}

			if (Nette\Utils\Strings::endsWith($item->fullURI, '_search') === FALSE || !in_array($item->method, ['GET', 'POST'])) {
				continue; // explain only search queries
			}

			if (!is_array($data = $extractData($item->response))) {
				continue;
			}

			try {
				$explode = explode('/', $item->fullURI);
				$path = '/' . implode('/', array_slice($explode, count($explode) - 3));

				$response = self::$panel->connection->performRequest(
					$item->method,
					$path,
					$item->headers,
					Json::encode(['explain' => 1] + $extractData($item->body))
				);

				// replace the search response with the explained response
				$processedQueries[$host][$i]->explain = $response;

			}
			catch (\Exception $e) {
//				dd($e);
				// ignore
			}

		}
		require __DIR__ . '/panel.phtml';

		return ob_get_clean();
	}


	public function success($method, $fullURI, $body, $headers, $statusCode, $response, $duration)
	{
		self::$panel->queries[] = Nette\Utils\ArrayHash::from([
			'method' => $method,
			'fullURI' => $fullURI,
			'body' => $body,
			'headers' => $headers,
			'statusCode' => $statusCode,
			'response' => $response,
			'duration' => $duration
		]);
		self::$panel->totalTime += $duration;
		self::$panel->queriesCount++;
	}


	public function failure($method, $fullURI, $body, $headers, $duration, $statusCode, $response, $exception)
	{
		self::$panel->queries[] = Nette\Utils\ArrayHash::from([
			'method' => $method,
			'fullURI' => $fullURI,
			'body' => $body,
			'headers' => $headers,
			'duration' => $duration,
			'statusCode' => $statusCode,
			'response' => $response,
			'exception' => $exception
		]);
		self::$panel->totalTime += $duration;
		self::$panel->queriesCount++;
	}

	/**
	 * @param Connection $connection
	 *
	 * @return Panel
	 */
	public static function register(Connection $connection)
	{
		if (self::$panel === NULL) {
			self::$panel = new self;
			self::getDebuggerBar()->addPanel(self::$panel);
		}

		if (self::$panel->connection === NULL) self::$panel->connection = $connection;

		return self::$panel;
	}


	/**
	 * @return Bar
	 */
	private static function getDebuggerBar()
	{
		return method_exists('Tracy\Debugger', 'getBar') ? Debugger::getBar() : Debugger::$bar;
	}

}
