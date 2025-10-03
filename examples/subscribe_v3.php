<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\Options;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Transport\TcpTransport;

require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/config.php';

$host       = $config['host'];
$port       = $config['port']     ?? 1883;
$scheme     = $config['scheme']   ?? 'tcp';
$username   = $config['username'] ?? null;
$password   = $config['password'] ?? null;
$tlsOptions = $config['tls']      ?? null;

$options = new Options(host: $host, port: $port, version: MqttVersion::V3_1_1);
$options = $options
    ->withClientId('php-iot-sub-v3')
    ->withKeepAlive(30)
    ->withCleanSession(true);
if ($username !== null) {
    $options = $options->withUser($username, $password);
}
if ($scheme === 'tls') {
    $options = $options->withTls($tlsOptions);
}

$client = new ScienceStories\Mqtt\Client\Client($options, new TcpTransport());
$client->connect();

$client->subscribe(['devices/#'], 0);

$deadline = microtime(true) + 60.0; // run for 60 seconds
while (microtime(true) < $deadline) {
    $msg = $client->awaitMessage(1.0);
    if ($msg) {
        echo $msg->topic.' => '.$msg->payload.PHP_EOL;
    }
}

$client->disconnect('finished');
