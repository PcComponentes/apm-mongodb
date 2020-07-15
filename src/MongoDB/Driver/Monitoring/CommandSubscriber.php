<?php
declare(strict_types=1);

namespace PcComponentes\ElasticAPM\MongoDB\Driver\Monitoring;

use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber as CommandSubscriberBase;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use ZoiloMora\ElasticAPM\Events\Span\Context;
use ZoiloMora\ElasticAPM\Events\Span\Context\Db;
use ZoiloMora\ElasticAPM\Events\Span\Span;

final class CommandSubscriber implements CommandSubscriberBase
{
    private const SPAN_NAME = 'DB Query';
    private const SPAN_TYPE = 'DB';
    private const SPAN_SUBTYPE = 'mongodb';
    private const SPAN_ACTION = 'query';
    private const CONTEXT_DB_TYPE = 'mongodb';
    private const STACKTRACE_SKIP = 6;

    private ElasticApmTracer $elasticApmTracer;

    private ?Span $span;

    public function __construct(ElasticApmTracer $elasticApmTracer)
    {
        $this->elasticApmTracer = $elasticApmTracer;
        $this->span = null;
    }

    public function commandStarted(CommandStartedEvent $event)
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $this->span = $this->elasticApmTracer->startSpan(
            self::SPAN_NAME,
            self::SPAN_TYPE,
            self::SPAN_SUBTYPE,
            self::SPAN_ACTION,
            $this->getContext($event),
            self::STACKTRACE_SKIP,
        );
    }

    public function commandSucceeded(CommandSucceededEvent $event)
    {
        if (null === $this->span) {
            return;
        }

        $this->span->stop();
    }

    public function commandFailed(CommandFailedEvent $event)
    {
        if (null === $this->span) {
            return;
        }

        $this->span->stop();
    }

    private function getContext(CommandStartedEvent $event): Context
    {
        return Context::fromDb(
            new Db(
                $this->getInstance($event),
                null,
                $this->getStatement($event),
                self::CONTEXT_DB_TYPE,
            ),
        );
    }

    private function getInstance(CommandStartedEvent $event): string
    {
        $server = $event->getServer();

        return \sprintf(
            '%s:%d',
            $server->getHost(),
            $server->getPort(),
        );
    }

    private function getStatement(CommandStartedEvent $event): string
    {
        $command = (array) $event->getCommand();

        $database = $command['$db'];
        $action = 'unknown';
        $collection = '';
        $filter = '';

        if (true === \array_key_exists('filter', $command)) {
            $filter = (array) $command['filter'];
        }

        // TODO Implement Lexer
        if (true === \array_key_exists('find', $command)) {
            $action = 'find';
            $collection = $command['find'];
        }

        $limit = null;

        if (true === \array_key_exists('limit', $command)) {
            $limit = $command['limit'];
        }

        $statement = [
            'database' => $database,
            'collection' => $collection,
            'action' => $action,
            'filter' => $filter,
            'limit' => $limit,
        ];

        return \json_encode($statement, \JSON_PRETTY_PRINT);
    }
}
