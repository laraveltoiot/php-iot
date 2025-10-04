<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * MQTT UNSUBSCRIBE packet model for MQTT 3.1.1 and 5.0.
 *
 * The UNSUBSCRIBE packet is sent from the client to the broker to remove one or more subscriptions.
 * After unsubscribing, the broker will no longer send PUBLISH messages for those topic filters to the client.
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple unsubscription with topic filter list only
 * - MQTT 5.0: Adds properties field for enhanced features (user_properties)
 *
 * Topic Filters:
 * - Must match exactly the topic filter used in the original SUBSCRIBE
 * - Can include wildcards:
 *   * Single-level wildcard (+): "home/+/temperature"
 *   * Multi-level wildcard (#): "home/#"
 * - The topic filter must match the subscription exactly (including wildcards)
 *
 * Important Notes:
 * - Unsubscribing from a non-existent subscription is not an error (MQTT 3.1.1)
 * - MQTT 5.0 provides reason codes in UNSUBACK to indicate success/failure per topic
 * - No QoS level in UNSUBSCRIBE (QoS was only relevant for the subscription itself)
 * - Packet Identifier is required to track the UNSUBACK response
 *
 * MQTT 5.0 Properties:
 * - user_properties: Custom key-value pairs for application metadata
 *
 * UNSUBACK Response:
 * The broker responds with an UNSUBACK packet containing:
 * - MQTT 3.1.1: Packet Identifier only (implicit success)
 * - MQTT 5.0: Packet Identifier + reason codes per topic filter
 *   * 0x00: Success - subscription deleted
 *   * 0x11: No subscription existed
 *   * 0x80+: Various error codes
 *
 * Usage Examples:
 * ```php
 * // Simple unsubscribe from a single topic (MQTT 3.1.1 and 5.0)
 * $client->unsubscribe(['sensors/temperature']);
 *
 * // Unsubscribe from multiple topics (MQTT 3.1.1 and 5.0)
 * $client->unsubscribe([
 *     'sensors/temperature',
 *     'sensors/humidity',
 *     'alerts/#',
 * ]);
 *
 * // Unsubscribe from wildcard subscriptions (MQTT 3.1.1 and 5.0)
 * $client->unsubscribe([
 *     'home/+/temperature', // Single-level wildcard
 *     'sensors/#',          // Multi-level wildcard
 * ]);
 *
 * // Unsubscribe from all subscriptions (MQTT 3.1.1 and 5.0)
 * $client->unsubscribe([
 *     'devices/status',
 *     'config/#',
 *     'data/stream',
 * ]);
 *
 * // MQTT 5.0 unsubscribe (internally uses properties if version is v5)
 * // Properties are automatically handled by the encoder based on MQTT version
 * $client->unsubscribe([
 *     'monitoring/metrics',
 *     'logs/#',
 * ]);
 * ```
 *
 * Packet Structure:
 *
 * MQTT 3.1.1:
 * - Fixed Header: Type (10), reserved flags (0x02), Remaining Length
 * - Variable Header: Packet Identifier (2 bytes)
 * - Payload: List of topic filters (UTF-8 strings)
 *
 * MQTT 5.0:
 * - Fixed Header: Type (10), reserved flags (0x02), Remaining Length
 * - Variable Header: Packet Identifier (2 bytes) + Properties (varint length + data)
 * - Payload: List of topic filters (UTF-8 strings)
 *
 * Comparison with SUBSCRIBE:
 * - SUBSCRIBE: Creates subscriptions, includes QoS levels, uses SUBACK response
 * - UNSUBSCRIBE: Removes subscriptions, no QoS levels, uses UNSUBACK response
 * - Both require exact topic filter match (including wildcards)
 * - Both use Packet Identifier for tracking responses
 */
final class Unsubscribe
{
    /**
     * @param  non-empty-list<string>  $filters  List of topic filters to unsubscribe from
     * @param  int  $packetId  Packet identifier for tracking the unsubscription request (1-65535)
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 UNSUBSCRIBE properties. Supported keys:
     *                                                  - user_properties: array<string, string> (custom metadata)
     */
    public function __construct(
        public array $filters,
        public int $packetId,
        public ?array $properties = null,
    ) {
    }
}
