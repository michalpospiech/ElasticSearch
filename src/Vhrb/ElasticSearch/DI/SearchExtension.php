<?php

namespace Vhrb\ElasticSearch\DI;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
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
		),
//		'connectionClass' => '\Vhrb\ElasticSearch\Connection',
	);


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		unset($config['debugger']);
		foreach ($config['hosts'] as $key => $host) {
			if ($key == 'port') {
				trigger_error('Param "port" is deprecated. Use host:port', E_USER_DEPRECATED);
			}
		}

		$builder->addDefinition($this->prefix('client'))
			->setFactory(ClientBuilder::class . "::fromConfig", [$config])
			->setClass(Client::class);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		return;
		$config = $this->getConfig($this->defaults);

		$initialize = $class->methods['initialize'];
		$initialize->addBody('?::$ELASTIC_DEBUGGER = ?;', array(new Code\PhpLiteral('Vhrb\ElasticSearch\DI\SearchExtension'), $config['debugger']));
	}
}

