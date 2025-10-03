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

$clientId = 'php-iot-fallback';
try {
    $clientId = 'php-iot-'.RandomId::clientId(6);
} catch (RandomException $e) {
    // fallback stays
}

$port = $config['port'] ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);

$options = new Options(
    host: $config['host'],
    port: $port,
    version: MqttVersion::V3_1_1,
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

$client = new Client($options, new TcpTransport());

try {
    $result = $client->connect();
    echo "✅ [v3] Connected to {$config['host']}:{$options->port}\n";
    echo '   SessionPresent='.($result->sessionPresent ? 'yes' : 'no')."\n";
    echo '   ReturnCode='.$result->reasonCode."\n";

    // Set production-like topic and message here
    $topic = 'devices/example/v3';
    $msg   = 'Hello from MQTT v3 example';
    $client->publish($topic, $msg);

    $client->disconnect();
    echo "👋 Disconnected\n";
} catch (Throwable $e) {
    fwrite(STDERR, '❌ Connection failed: '.$e->getMessage()."\n");
    exit(1);
}
