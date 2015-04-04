<?php

namespace Vhrb\ElasticSearch\DI;

use Vhrb;
use Nette;
use Nette\PhpGenerator as Code;

class SearchExtension extends Nette\DI\CompilerExtension
{
	public static $ELASTIC_DEBUGGER = FALSE;

	/**
	 * @var array
	 */
	public $defaults = array(
		'debugger' => '%debugMode%',
		'hosts' => array(
			'host' => '127.0.0.1',
			'port' => 9200,
		),
		'connectionClass' => '\Vhrb\ElasticSearch\Connection',
	);


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		unset($config['debugger']);

		$builder->addDefinition($this->prefix('client'))
			->setClass('Elasticsearch\Client', [$config]);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$config = $this->getConfig($this->defaults);

		$initialize = $class->methods['initialize'];
		$initialize->addBody('?::$ELASTIC_DEBUGGER = ?;', array(new Code\PhpLiteral('Vhrb\ElasticSearch\DI\SearchExtension'), $config['debugger']));
	}
}

