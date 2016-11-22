<?php

namespace Vhrb\ElasticSearch\DI;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use Vhrb;
use Nette;
use Nette\PhpGenerator as Code;
use Vhrb\ElasticSearch\Tracy\Panel;

class SearchExtension extends Nette\DI\CompilerExtension
{
	public static $ELASTIC_DEBUGGER = FALSE;

	/**
	 * @var array
	 */
	public $defaults = array(
		'debugger' => '%debugMode%',
	);

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$debugger = $config['debugger'];
		unset($config['debugger']);
		if ($debugger) {
			$builder->addDefinition($this->prefix('panel'))
				->setClass(Panel::class);
			$config['logger'] = $this->prefix('@panel');
		}

		$elastic = $builder->addDefinition($this->prefix('client'))
			->setFactory(ClientBuilder::class . "::fromConfig", [$config])
			->setClass(Client::class);

		if ($debugger) {
			$elastic->addSetup($this->prefix('@panel') . '::register', []);
		}
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$config = $this->getConfig($this->defaults);

		$initialize = $class->methods['initialize'];
		$initialize->addBody('?::$ELASTIC_DEBUGGER = ?;', array(new Code\PhpLiteral('Vhrb\ElasticSearch\DI\SearchExtension'), $config['debugger']));
	}
}

