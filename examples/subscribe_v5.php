<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\Options;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Transport\TcpTransport;
use ScienceStories\Mqtt\Util\ConsoleLogger;

require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/config.php';

$host       = $config['host'];
$port       = $config['port']     ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);
$scheme     = $config['scheme']   ?? 'tls';
$username   = $config['username'] ?? null;
$password   = $config['password'] ?? null;
$tlsOptions = $config['tls']      ?? null;

$options = new Options(host: $host, port: $port, version: MqttVersion::V5_0);
$options = $options
    ->withClientId('php-iot-sub-v5')
    ->withKeepAlive(30)
    ->withCleanSession(true);
if ($username !== null) {
    $options = $options->withUser($username, $password);
}
if ($scheme === 'tls') {
    $options = $options->withTls($tlsOptions);
}

$client = new ScienceStories\Mqtt\Client\Client($options, new TcpTransport(), logger: new ConsoleLogger());
$client->connect();

// Subscribe to a shared subscription for load-balanced processing (broker must support v5 shared subs)
$filter = '$share/groupA/devices/+/telemetry';
$client->subscribe([$filter], 0);

$deadline = microtime(true) + 60.0; // run for 60 seconds
while (microtime(true) < $deadline) {
    $msg = $client->awaitMessage(0.5);
    if ($msg) {
        fprintf(STDERR, "[%s] %s => %s\n", date('H:i:s'), $msg->topic, $msg->payload);
    }
}

$client->disconnect('finished');
