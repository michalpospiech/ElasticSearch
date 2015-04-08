# ElasticSearch

Integration official PHP client for Elasticsearch into Nette Framework

Inspired by [Kdyby/Search](https://github.com/Kdyby/ElasticSearch)


Requirements
------------

Vhrb/ElasticSearch requires PHP 5.3 or higher.

- [Nette Framework](https://github.com/nette/nette)
- [Elaticsearch-php](https://github.com/elastic/elasticsearch-php)

## Install

The best way to install Kdyby/ElasticSearch is using  [Composer](http://getcomposer.org/):

```sh
$ composer require vhrb/elastic-search:@dev
```

```yml
extension:
	elastic: Vhrb\ElasticSearch\DI\SearchExtension
```

## Config

Optional

```yml
elastic:
	hosts:
		host: 127.0.0.1
		port: 9200
	debugger: %debugMode%	
	...
```

[Elastic search config](http://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_full_list_of_configurations)

## Use of

```php
class EsService {

	/** @var Elasticsearch\Client */
	private $client;
	
	public function __construct(Elasticsearch\Client $client) {
		$this->client = $client;
	}
}
```
Client implement all [ElasticSearch-php](http://www.elastic.co/guide/en/elasticsearch/client/php-api/current/) methods!

## Custom use

```php
class EsService {

	protected function getPanel() {
		if($this->panel === NULL) $this->panel = Panel::register($this);
		return $this->panel;
	}
	
	public function request() {
		...
		
		$this->getPanel()->->failure($method, $fullURI, $body, $headers, $duration, $statusCode, $response, $exception);

		//

		$this->panel->success($method, $fullURI, $body, $headers, $statusCode, $response, $duration);
	}

```

-----
Homepage [https://www.vhrb.cz](https://www.vhrb.cz) / [Vhřb](https://github.com/vhrb).

