<?php

declare(strict_types=1);

use Random\RandomException;
use ScienceStories\Mqtt\Client\Client;
use ScienceStories\Mqtt\Client\Options;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Transport\TcpTransport;
use ScienceStories\Mqtt\Util\RandomId;

require __DIR__.'/../vendor/autoload.php';

// Load shared broker config
$config = require __DIR__.'/config.php';

// Setup options
$clientId = 'php-iot-fallback';
try {
    $clientId = 'php-iot-'.RandomId::clientId(6);
} catch (RandomException $e) {
    // Keep fallback client ID
}

$port = $config['port'] ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);

$options = new Options(
    host: $config['host'],
    port: $port,
    version: MqttVersion::V5_0,
)
    ->withClientId($clientId)
    ->withKeepAlive(30)
    ->withCleanSession(true);

if (($config['username'] ?? null) !== null) {
    $options = $options->withUser($config['username'], $config['password'] ?? null);
}

if (($config['scheme'] ?? 'tcp') === 'tls') {
    $options = $options->withTls($config['tls'] ?? [
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);
}

// Init transport + client
$transport = new TcpTransport();
$client    = new Client($options, $transport);

try {
    $result = $client->connect();
    echo "✅ Connected to broker {$config['host']}:{$options->port}\n";
    echo '   SessionPresent='.($result->sessionPresent ? 'yes' : 'no')."\n";
    echo '   ReturnCode='.$result->reasonCode."\n";

    // Example publish (set your own topic/message as in production)
    $topic = 'devices/example/online';
    $msg   = 'Hello from connect_online example';
    $client->publish($topic, $msg);

    $client->disconnect();
    echo "👋 Disconnected\n";
} catch (Throwable $e) {
    fwrite(STDERR, '❌ Connection failed: '.$e->getMessage()."\n");
    exit(1);
}
