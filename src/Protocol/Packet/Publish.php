<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

use ScienceStories\Mqtt\Protocol\QoS;

/**
 * MQTT PUBLISH packet model for MQTT 3.1.1 and 5.0.
 *
 * The PUBLISH packet is used to transport application messages from client to broker or broker to client.
 * It contains the topic name, payload, QoS level, and optional properties (MQTT 5.0 only).
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple packet structure with topic, payload, QoS, retain, and dup flags
 * - MQTT 5.0: Adds properties field for enhanced features (content type, message expiry, topic aliases, etc.)
 *
 * QoS Levels:
 * - QoS 0 (At most once): Fire and forget, no acknowledgment required
 * - QoS 1 (At least once): Acknowledged delivery, requires PUBACK response
 * - QoS 2 (Exactly once): Assured delivery with four-step handshake (PUBREC, PUBREL, PUBCOMP)
 *
 * Flags:
 * - retain: If true, broker stores message as last known good value for the topic
 * - dup: Duplicate delivery flag (set automatically on QoS 1/2 retransmissions)
 *
 * MQTT 5.0 Properties:
 * - payload_format_indicator: Indicates if payload is UTF-8 text (1) or binary (0)
 * - message_expiry_interval: Lifetime of message in seconds (expires if not delivered)
 * - topic_alias: Integer representing topic name to reduce packet size
 * - response_topic: Topic name for request/response pattern
 * - correlation_data: Binary data to correlate request and response messages
 * - user_properties: Custom key-value pairs for application metadata
 * - content_type: MIME type describing the payload format (e.g., "application/json")
 *
 * Usage Examples:
 * ```php
 * // Simple QoS 0 publish (MQTT 3.1.1 and 5.0)
 * $publish = new Publish(
 *     topic: 'sensors/temperature',
 *     payload: '22.5',
 *     qos: QoS::AtMostOnce,
 * );
 *
 * // QoS 1 publish with retain (MQTT 3.1.1 and 5.0)
 * $publish = new Publish(
 *     topic: 'status/online',
 *     payload: 'true',
 *     qos: QoS::AtLeastOnce,
 *     retain: true,
 *     packetId: 123,
 * );
 *
 * // MQTT 5.0 publish with properties
 * $publish = new Publish(
 *     topic: 'data/json',
 *     payload: '{"temp":22.5}',
 *     qos: QoS::AtMostOnce,
 *     properties: [
 *         'content_type' => 'application/json',
 *         'message_expiry_interval' => 3600,
 *         'payload_format_indicator' => 1, // UTF-8 text
 *         'user_properties' => ['source' => 'sensor-01', 'location' => 'warehouse'],
 *     ],
 * );
 *
 * // MQTT 5.0 request/response pattern
 * $publish = new Publish(
 *     topic: 'request/device/status',
 *     payload: 'get-status',
 *     qos: QoS::AtLeastOnce,
 *     packetId: 456,
 *     properties: [
 *         'response_topic' => 'response/device/status',
 *         'correlation_data' => 'req-12345',
 *     ],
 * );
 * ```
 */
final class Publish
{
    /**
     * @param  string  $topic  Topic name to publish to (e.g., "sensors/temperature", "home/+/status")
     * @param  string  $payload  Message payload (can be text or binary data)
     * @param  QoS  $qos  Quality of Service level (0 = at most once, 1 = at least once, 2 = exactly once)
     * @param  bool  $retain  If true, broker retains a message as last known good value for the topic
     * @param  bool  $dup  Duplicate delivery flag (automatically set on retransmissions for QoS 1/2)
     * @param  int|null  $packetId  Packet identifier (required for QoS 1 and QoS 2, must be 1-65535)
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 publishes properties. Supported keys:
     *                                                  - payload_format_indicator: 0|1 or bool (0=binary, 1=UTF-8 text)
     *                                                  - message_expiry_interval: int (message lifetime in seconds, 0=no expiry)
     *                                                  - topic_alias: int (1-65535, integer alias for topic to reduce bandwidth)
     *                                                  - response_topic: string (topic for request/response pattern responses)
     *                                                  - correlation_data: string (binary data to correlate request/response)
     *                                                  - user_properties: array<string, string> (custom key-value metadata)
     *                                                  - content_type: string (MIME type, e.g., "application/json", "text/plain")
     */
    public function __construct(
        public string $topic,
        public string $payload,
        public QoS $qos = QoS::AtMostOnce,
        public bool $retain = false,
        public bool $dup = false,
        public ?int $packetId = null,
        public ?array $properties = null,
    ) {
    }
}
