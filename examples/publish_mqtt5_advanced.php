<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;

require __DIR__.'/../vendor/autoload.php';

// Load shared broker config
$config = require __DIR__.'/config.php';

// Advanced MQTT 5.0 example with all properties
// Demonstrates: user properties, content type, message expiry, payload format, etc.

$host    = $config['host'];
$topic   = 'devices/sensor-42/telemetry';
$payload = json_encode([
    'temperature' => 24.8,
    'humidity'    => 65.2,
    'pressure'    => 1013.25,
    'timestamp'   => time(),
]);

$tls = ($config['scheme'] ?? 'tcp') === 'tls';

// MQTT 5.0 publish properties
$properties = [
    'payload_format_indicator' => 1,                      // 1 = UTF-8 text
    'message_expiry_interval'  => 300,                    // 5 minutes TTL
    'content_type'             => 'application/json',     // Content type
    'response_topic'           => 'devices/sensor-42/responses', // For request/response
    'correlation_data'         => bin2hex(random_bytes(8)), // Correlation ID
    'user_properties'          => [                       // Custom metadata
        'device_id'   => 'sensor-42',
        'location'    => 'warehouse-A',
        'firmware'    => '1.2.3',
        'environment' => 'production',
    ],
];

Mqtt::publish(
    host: $host,
    topic: $topic,
    payload: $payload,
    version: 'v5',                         // MQTT 5.0
    tls: $tls,
    username: $config['username'] ?? null,
    password: $config['password'] ?? null,
    qos: QoS::ExactlyOnce,                 // QoS 2 - exactly once delivery
    retain: true,                           // Retain for new subscribers
    properties: $properties,                // MQTT 5 properties
    tlsOptions: $config['tls'] ?? null,
    keepAlive: 60,
    cleanStart: true,
);

echo "✅ MQTT 5.0 message published to {$topic}\n";
echo "   QoS: 2 (Exactly Once)\n";
echo "   Retain: true\n";
echo "   Properties:\n";
echo "     - Content-Type: application/json\n";
echo "     - Message Expiry: 300 seconds\n";
echo "     - Response Topic: devices/sensor-42/responses\n";
echo '     - User Properties: '.count($properties['user_properties'])." custom fields\n";
