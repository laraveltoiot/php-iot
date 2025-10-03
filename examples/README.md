# MQTT Client Examples

This folder contains examples showing how to use the PHP IoT MQTT Client, from simple to advanced usage.

## Quick Start

### 1. Configure Your Broker

Copy `.env.example` to `.env` in the project root and configure your MQTT broker:

```env
MQTT_HOST=your-broker.example.com
MQTT_SCHEME=tls          # or 'tcp' for non-TLS
MQTT_PORT=8883           # or 1883 for non-TLS
MQTT_USERNAME=your_user
MQTT_PASSWORD=your_pass
```

### 2. Run Examples

## Examples Overview

### Publishing Messages

Examples are ordered by complexity—start with the simplest and progress as needed.

#### 1. **simple_publish.php—**Minimal Example

The simplest way to publish a message. Just 3 required parameters:

```php
use ScienceStories\Mqtt\Easy\Mqtt;

Mqtt::publish($host, $topic, $payload);
```

**Use this when:** You need the quickest way to send a message to a public broker without authentication.

---

#### 2. **publish_with_auth.php—**Production Ready

Adds authentication, TLS encryption, and QoS delivery guarantees:

```php
use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Protocol\QoS;

Mqtt::publish(
    host: $host,
    topic: $topic,
    payload: $payload,
    tls: true,
    username: $username,
    password: $password,
    qos: QoS::AtLeastOnce,
    retain: false,
);
```

**Use this when:** Publishing to a secure broker (most production scenarios).

**Features shown:**
- TLS/SSL encryption
- Username/password authentication  
- QoS 1 (at least once delivery)
- JSON payload encoding

---

#### 3. **publish_mqtt5_advanced.php—**Full MQTT 5.0 Features

Demonstrates all MQTT 5.0 publish properties:

```php
$properties = [
    'payload_format_indicator' => 1,              // UTF-8 text
    'message_expiry_interval'  => 300,            // TTL in seconds
    'content_type'             => 'application/json',
    'response_topic'           => 'devices/sensor-42/responses',
    'correlation_data'         => bin2hex(random_bytes(8)),
    'user_properties'          => [               // Custom metadata
        'device_id' => 'sensor-42',
        'location'  => 'warehouse-A',
    ],
];

Mqtt::publish(
    host: $host,
    topic: $topic,
    payload: $payload,
    version: 'v5',
    qos: QoS::ExactlyOnce,
    retain: true,
    properties: $properties,
);
```

**Use this when:** You need MQTT 5.0 features like message expiry, content type negotiation, or request/response patterns.

**Features shown:**
- MQTT 5.0 protocol version
- QoS 2 (exactly once delivery)
- Message retention
- Payload format indicator
- Message expiry
- Content type
- Response topic (for request/response)
- Correlation data
- User properties (custom metadata)

---

#### 4. **reusable_client.php** - Multiple Messages

Efficient pattern for publishing multiple messages with a single connection:

```php
use ScienceStories\Mqtt\Easy\Mqtt;
use ScienceStories\Mqtt\Client\PublishOptions;
use ScienceStories\Mqtt\Protocol\QoS;

// Connect once
$client = Mqtt::connect(
    host: $host,
    port: $port,
    version: 'v5',
    tls: true,
    username: $username,
    password: $password,
);

try {
    // Publish multiple messages
    foreach ($messages as $topic => $payload) {
        $client->publish($topic, $payload, new PublishOptions(
            qos: QoS::AtLeastOnce
        ));
    }
} finally {
    $client->disconnect();
}
```

**Use this when:** Publishing multiple messages in a loop or batch job.

**Benefits:**
- Faster (no repeated connection overhead)
- More efficient network usage
- Better for high-frequency publishing

---

### Subscribing to Messages

#### **subscribe_v3.php** / **subscribe_v5.php**

Subscribe to topics and receive messages:

```php
$client->subscribe(['devices/+/data'], qos: 1);

foreach ($client->messages() as $message) {
    echo "Topic: {$message->topic}\n";
    echo "Payload: {$message->payload}\n";
}
```

#### **monitor_v5.php**

Monitor topics with callback handler pattern.

---

### Low-Level Examples

#### **connect_online.php**

Manual low-level connection using `Client`, `Options`, and `Transport` directly.

**Use this when:** You need full control over client configuration or want to understand the internals.

---

## API Quick Reference

### Mqtt::publish()—Simplified API

```php
Mqtt::publish(
    string $host,              // REQUIRED: Broker hostname
    string $topic,             // REQUIRED: MQTT topic
    string $payload,           // REQUIRED: Message payload
    ?int $port = null,         // Auto: 1883 (TCP) or 8883 (TLS)
    string $version = 'v3',    // 'v3' or 'v5'
    bool $tls = false,         // Enable TLS/SSL
    ?string $username = null,  // Authentication username
    ?string $password = null,  // Authentication password
    ?QoS $qos = null,          // QoS::AtMostOnce (default), AtLeastOnce, ExactlyOnce
    bool $retain = false,      // Retain message for new subscribers
    ?array $tlsOptions = null, // Custom TLS context options
    ?array $properties = null, // MQTT 5 properties
);
```

### Mqtt::connect() - Reusable Client

```php
$client = Mqtt::connect(
    string $host,
    int $port,
    string $version = 'v3',
    bool $tls = false,
    ?string $username = null,
    ?string $password = null,
    ?array $tlsOptions = null,
    ?string $clientId = null,
    int $keepAlive = 60,
    bool $cleanStart = true,
): ClientInterface;
```

---

## QoS Levels

```php
use ScienceStories\Mqtt\Protocol\QoS;

QoS::AtMostOnce   // QoS 0 - Fire and forget (fastest)
QoS::AtLeastOnce  // QoS 1 - Guaranteed delivery (most common)
QoS::ExactlyOnce  // QoS 2 - Exactly once (slowest, highest guarantee)
```

---

## Configuration File

All examples (except `simple_publish.php`) use `config.php` which loads settings from `.env`:

```php
// examples/config.php automatically loads from .env
$config = require __DIR__.'/config.php';

$host     = $config['host'];
$port     = $config['port'];
$username = $config['username'];
$password = $config['password'];
$tls      = $config['scheme'] === 'tls';
```

---

## Best Practices

### For Quick Testing
✅ Use `simple_publish.php` pattern with public broker

### For Production
✅ Use `publish_with_auth.php` pattern with TLS + auth  
✅ Use QoS 1 (AtLeastOnce) for important messages  
✅ Enable TLS/SSL for public networks  
✅ Use strong credentials

### For High Volume
✅ Use `reusable_client.php` pattern  
✅ Batch messages with single connection  
✅ Consider QoS 0 for non-critical data

### For MQTT 5.0
✅ Use `publish_mqtt5_advanced.php` as template  
✅ Set `version: 'v5'`  
✅ Use properties for metadata and TTL  
✅ Leverage user properties for custom fields

---

## Troubleshooting

### Connection Timeout
- Check broker hostname and port
- Verify firewall allows outbound connections
- Test broker availability: `telnet hostname port`

### Authentication Failed
- Verify username and password in `.env`
- Check broker access control lists (ACL)

### TLS Errors
- Ensure `MQTT_SCHEME=tls` in `.env`
- Use port 8883 for TLS (not 1883)
- Check broker certificate validity

---

## Need Help?

- Check the main [README.md](../README.md)
- Review [Client.php](../src/Client/Client.php) for full API
- See [Options.php](../src/Client/Options.php) for all configuration options
