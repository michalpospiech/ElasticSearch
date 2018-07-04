<?php

namespace Vhrb\ElasticSearch\Tracy;

use Nette;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\Helpers;
use Tracy\IBarPanel;


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

class Panel implements IBarPanel, LoggerInterface
{
	use Nette\SmartObject;
	
	private static $log = FALSE;

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
	public $queries = array();

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		$img = Html::el('img', ['height' => '16px'])
			->src('data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/logo.png')));
		$tab = Html::el('span')->title('ElasticSearch')->addHtml($img);
		$title = Html::el()->setText('ElasticSearch');

		if ($this->queriesCount) {
			$title->setText(
				$this->queriesCount . ' call' . ($this->queriesCount > 1 ? 's' : '') .
				' / ' . sprintf('%0.2f', $this->totalTime * 1000) . ' ms'
			);
		}

		return (string) $tab->addText($title);
	}

	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (!$this->queries) {
//			return NULL;
		}

		ob_start();
		$esc = function ($s) {
			return Helpers::escapeHtml($s);
		};
		$click = function ($o, $c = FALSE) {
			return \Tracy\Dumper::toHtml($o, [Dumper::COLLAPSE => $c, Dumper::DEPTH => Debugger::$maxDepth]);
		};
		$totalTime = $this->totalTime ? sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : 'none';

		$queries = $this->queries;
		$processedQueries = [];
		foreach ($queries as $i => $item) {
			$processedQueries[''][$i] = $item;
		}
		require __DIR__ . '/panel.phtml';

		return ob_get_clean();
	}


	/**
	 * @return Bar
	 */
	public function register()
	{
		return Debugger::getBar()->addPanel($this);
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function emergency($message, array $context = array())
	{
		// @TODO: Implement emergency() method.
		if (self::$log) bd('emergency', $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function alert($message, array $context = array())
	{
		// @TODO: Implement alert() method.
		if (self::$log) bd('alert', $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function critical($message, array $context = array())
	{
		// @TODO: Implement critical() method.
		if (self::$log) bd('critical', $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function error($message, array $context = array())
	{
		// @TODO: Implement error() method.
		if (self::$log) bd('error', $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function warning($message, array $context = array())
	{
		// @TODO: Implement warning() method.
		if (self::$log) bd('warning', $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function notice($message, array $context = array())
	{
		// @TODO: Implement notice() method.
		if (self::$log) bd('notice', $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function info($message, array $context = array())
	{
		// @TODO: Implement info() method.
		if (self::$log) bd('info', $message, $context);
		$this->queries[] = [
				'message' => $message,
			] + $context;

		$this->totalTime = $context['duration'];
		$this->queriesCount++;
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function debug($message, array $context = array())
	{
		// @TODO: Implement debug() method.
		if (self::$log) bd('debug', $message, $context);

		if ($message == "Response") {
			$this->queries[] = [
				'message' => $message,
				'response' => $context[0],
			];
		}
		else {
			try {
				$json = Json::decode($context[0], Json::FORCE_ARRAY);
			} catch (Nette\Utils\JsonException $e) {
				$json = array();
			}

			$this->queries[] = [
				'message' => $message,
				'body' => $json,
			];
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function log($level, $message, array $context = array())
	{
		// @TODO: Implement log() method.
		if (self::$log) bd('log', $message, $context);
	}
}
