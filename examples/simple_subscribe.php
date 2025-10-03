<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Easy\Mqtt;

require __DIR__.'/../vendor/autoload.php';

// Minimal subscriber example: connect and listen for messages
// Run this in a separate terminal before running simple_publish.php
//
// Uses config.php for broker settings (automatically loads TLS, auth, port from .env)
$config = require __DIR__.'/config.php';

$host  = $config['host'];
$port  = $config['port'];
$tls   = ($config['scheme'] ?? 'tcp') === 'tls';
$topic = 'php-iot/test';

echo "🔌 Connecting to {$host}:{$port}...\n";
echo "📡 Subscribing to topic: {$topic}\n";
echo "⏳ Waiting for messages (press Ctrl+C to stop)...\n\n";

$client = Mqtt::connect(
    host: $host,
    port: $port,
    tls: $tls,
    username: $config['username'] ?? null,
    password: $config['password'] ?? null,
    tlsOptions: $config['tls']    ?? null,
);

try {
    $client->subscribe([$topic], 0);

    // Listen for messages indefinitely
    foreach ($client->messages(0.5) as $msg) {
        echo sprintf(
            "📨 Received: %s\n   Topic: %s\n   QoS: %d\n   Time: %s\n\n",
            $msg->payload,
            $msg->topic,
            $msg->qos->value,
            date('Y-m-d H:i:s')
        );
    }
} finally {
    $client->disconnect();
    echo "👋 Disconnected\n";
}
