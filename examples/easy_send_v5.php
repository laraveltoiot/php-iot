<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;

require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/config.php';

$host       = $config['host'];
$port       = $config['port'] ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);
$version    = 'v5';
$tls        = ($config['scheme'] ?? 'tcp') === 'tls';
$username   = $config['username'] ?? null;
$password   = $config['password'] ?? null;
$tlsOptions = $config['tls']      ?? null;

// Define your production-like topic and payload here
$topic   = 'devices/example/easy-v5';
$payload = 'Hello from Easy MQTT v5!';

// Demonstrate optional MQTT 5 properties
$properties = [
    'payload_format_indicator' => 1, // 1 = UTF-8 text
    'message_expiry_interval'  => 30, // seconds
    'content_type'             => 'text/plain; charset=utf-8',
    'user_properties'          => [
        'app' => 'php-iot',
        'env' => 'dev',
    ],
    // 'response_topic' => 'responses/device-42',
    // 'correlation_data' => random_bytes(8),
    // 'topic_alias' => 1,
];

Mqtt::send(
    host: $host,
    port: $port,
    topic: $topic,
    payload: $payload,
    version: $version,
    tls: $tls,
    username: $username,
    password: $password,
    qos: QoS::AtMostOnce,
    retain: false,
    tlsOptions: $tlsOptions,
    properties: $properties,
);
