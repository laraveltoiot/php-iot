<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\PublishOptions;
use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;

require __DIR__.'/../vendor/autoload.php';

// Load shared broker config
$config = require __DIR__.'/config.php';

// Example: Connect once, publish multiple messages (more efficient)
// Use this pattern when publishing multiple messages in a loop or batch

$host = $config['host'];
$tls  = ($config['scheme'] ?? 'tcp') === 'tls';
$port = $config['port'] ?? ($tls ? 8883 : 1883);

// Connect once and get a reusable client
$client = Mqtt::connect(
    host: $host,
    port: $port,
    version: 'v5',
    tls: $tls,
    username: $config['username'] ?? null,
    password: $config['password'] ?? null,
    tlsOptions: $config['tls']    ?? null,
    keepAlive: 60,
    cleanStart: true,
);

echo "✅ Connected to {$host}:{$port}\n";

try {
    // Publish multiple messages using the same connection
    $sensors = [
        'sensor-01' => ['temperature' => 22.5, 'humidity' => 60.0],
        'sensor-02' => ['temperature' => 23.1, 'humidity' => 58.5],
        'sensor-03' => ['temperature' => 21.8, 'humidity' => 62.3],
    ];

    foreach ($sensors as $sensorId => $data) {
        $topic   = "devices/{$sensorId}/data";
        $payload = json_encode([
            ...$data,
            'timestamp' => time(),
        ]);

        $client->publish($topic, $payload, new PublishOptions(qos: QoS::AtLeastOnce));
        echo "📤 Published to {$topic}\n";

        // Small delay between publishes (optional)
        usleep(100000); // 100ms
    }

    echo "\n✅ All messages published successfully\n";
} finally {
    // Always disconnect when done
    $client->disconnect();
    echo "👋 Disconnected\n";
}
