# Elastic APM for MongoDB

This library supports Span traces of command using MongoDB.

## Installation

1) Install via [composer](https://getcomposer.org/)

    ```shell script
    composer require pccomponentes/apm-mongodb
    ```

## Usage

In all cases, an already created instance of [ElasticApmTracer](https://github.com/zoilomora/elastic-apm-agent-php) is assumed.

### Native PHP

```php
<?php
declare(strict_types=1);

use function MongoDB\Driver\Monitoring\addSubscriber;

$commandSubscriber = new PcComponentes\ElasticAPM\MongoDB\Driver\Monitoring\CommandSubscriber(
    $apmTracer, /** \ZoiloMora\ElasticAPM\ElasticApmTracer instance. */
);

addSubscriber($commandSubscriber);
```

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT)

Read [LICENSE](LICENSE) for more information
