<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;

require __DIR__.'/../vendor/autoload.php';

// Load shared broker config (with credentials and TLS settings)
$config = require __DIR__.'/config.php';

// Example with authentication, TLS, QoS, and retain
// Common production scenario

$host    = $config['host'];
$topic   = 'devices/sensor-01/temperature';
$payload = json_encode([
    'value'     => 23.5,
    'unit'      => 'celsius',
    'timestamp' => date('c'),
]);

// TLS is enabled if a scheme=tls in config
$tls = ($config['scheme'] ?? 'tcp') === 'tls';

Mqtt::publish(
    host: $host,
    topic: $topic,
    payload: $payload,
    tls: $tls,
    username: $config['username'] ?? null,
    password: $config['password'] ?? null,
    qos: QoS::AtLeastOnce,        // QoS 1 - guaranteed delivery
    retain: false,                  // Don't retain this message
    tlsOptions: $config['tls'] ?? null,
);

echo "✅ Message published to {$topic} with QoS 1\n";
echo "   Payload: {$payload}\n";
