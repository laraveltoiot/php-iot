<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\PublishOptions;
use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;
use ScienceStories\Mqtt\Util\ConsoleLogger;

require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/config.php';

$host       = $config['host'];
$port       = $config['port'] ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);
$version    = 'v5';
$tls        = ($config['scheme'] ?? 'tcp') === 'tls';
$username   = $config['username'] ?? null;
$password   = $config['password'] ?? null;
$tlsOptions = $config['tls']      ?? null;

$logger = new ConsoleLogger();

$client = Mqtt::connect(
    host: $host,
    port: $port,
    version: $version,
    tls: $tls,
    username: $username,
    password: $password,
    tlsOptions: $tlsOptions,
    clientId: null,
    keepAlive: 30,
    cleanStart: true,
    logger: $logger,
);

try {
    // Demonstrate a PING round-trip with logs
    $client->ping();

    // Publish a small message (logs will show PUBLISH details)
    $topic = $config['topic']   ?? 'test/php-iot/monitor';
    $msg   = $config['message'] ?? 'Hello with logs!';
    $client->publish($topic, $msg, new PublishOptions(
        qos: QoS::AtMostOnce,
        retain: false,
        properties: [
            'payload_format_indicator' => 1,
            'content_type'             => 'text/plain',
            'user_properties'          => ['app' => 'php-iot', 'demo' => 'monitor'],
        ],
    ));
} finally {
    $client->disconnect('done');
}
