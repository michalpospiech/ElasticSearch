<?php

namespace Vhrb\ElasticSearch;

use Elasticsearch\Transport;
use Vhrb\ElasticSearch\DI\SearchExtension;
use Vhrb\ElasticSearch\Tracy\Panel;
use Elasticsearch\Connections\Connection AS EC;
use Nette\Utils\Strings;

class Connection extends EC
{
	/** @var null|Panel */
	private $panel = NULL;

	public function performRequest($method, $uri, $params = NULL, $body = NULL, $options = [], Transport $transport)
	{
		if ($this->panel === NULL && SearchExtension::$ELASTIC_DEBUGGER) {
			$this->panel = Panel::register($this);
		}

		$response = parent::performRequest($method, $uri, $params, $body, $options, $transport);

		return $response;
	}

	public function logRequestFail($method, $fullURI, $body, $headers, $duration, $statusCode = NULL, $response = NULL, $exception = NULL)
	{
		parent::logRequestFail($method, $fullURI, $body, $headers, $duration, $statusCode, $response, $exception);

		if ($this->panel !== NULL)
			$this->panel->failure($method, $fullURI, $body, $headers, $duration, $statusCode, $response, $exception);
	}

	public function logRequestSuccess($method, $fullURI, $body, $headers, $statusCode, $response, $duration)
	{
		parent::logRequestSuccess($method, $fullURI, $body, $headers, $statusCode, $response, $duration);

		if ($this->panel !== NULL && !Strings::match($body, '~explain~')) {
			$this->panel->success($method, $fullURI, $body, $headers, $statusCode, $response, $duration);
		}
	}


}