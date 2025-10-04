<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

use ScienceStories\Mqtt\Client\SubscribeOptions;

/**
 * MQTT SUBSCRIBE packet model for MQTT 3.1.1 and 5.0.
 *
 * The SUBSCRIBE packet is sent from the client to the broker to create one or more subscriptions.
 * Each subscription registers the client's interest in one or more topics, allowing the broker to
 * deliver matching PUBLISH messages to the client.
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple subscription with topic filter and requested QoS level only
 * - MQTT 5.0: Adds subscription options (No Local, Retain As Published, Retain Handling) and properties
 *
 * Topic Filters:
 * - Can be exact topic names: "home/livingroom/temperature"
 * - Can include wildcards:
 *   * Single-level wildcard (+): "home/+/temperature" matches "home/livingroom/temperature", "home/kitchen/temperature"
 *   * Multi-level wildcard (#): "home/#" matches all topics under "home/"
 *
 * QoS Levels (Requested):
 * - QoS 0 (At most once): No acknowledgment, fire and forget
 * - QoS 1 (At least once): Acknowledged delivery may receive duplicates
 * - QoS 2 (Exactly once): Assured delivery, no duplicates
 * Note: The broker may grant a lower QoS than requested (returned in SUBACK)
 *
 * MQTT 5.0 Subscription Options:
 * - No Local: If true, messages published by this client are not sent back to it
 * - Retain As Published: If true, a retain flag is forwarded as published; if false, always 0
 * - Retain Handling:
 *   * 0 (default): Send retained messages at subscription time
 *   * 1: Send retained messages only if subscription doesn't exist
 *   * 2: Don't send retained messages at subscription time
 *
 * MQTT 5.0 Properties:
 * - subscription_identifier: Variable Byte Integer (1-268,435,455) to identify the subscription
 * - user_properties: Custom key-value pairs for application metadata
 *
 * Usage Examples:
 * ```php
 * // Simple subscription to a single topic (MQTT 3.1.1 and 5.0)
 * $filters = [
 *     ['filter' => 'sensors/temperature', 'qos' => 1],
 * ];
 * $client->subscribeWith($filters);
 *
 * // Multiple topics with different QoS levels (MQTT 3.1.1 and 5.0)
 * $filters = [
 *     ['filter' => 'sensors/temperature', 'qos' => 1],
 *     ['filter' => 'sensors/humidity', 'qos' => 1],
 *     ['filter' => 'alerts/#', 'qos' => 2],
 * ];
 * $client->subscribeWith($filters);
 *
 * // Wildcard subscriptions (MQTT 3.1.1 and 5.0)
 * $filters = [
 *     ['filter' => 'home/+/temperature', 'qos' => 0], // Single-level wildcard
 *     ['filter' => 'sensors/#', 'qos' => 1], // Multi-level wildcard
 * ];
 * $client->subscribeWith($filters);
 *
 * // MQTT 5.0 subscription with No Local option
 * $filters = [
 *     ['filter' => 'devices/status', 'qos' => 1],
 * ];
 * $options = new SubscribeOptions(noLocal: true);
 * $client->subscribeWith($filters, $options);
 *
 * // MQTT 5.0 subscription with Retain Handling
 * $filters = [
 *     ['filter' => 'config/#', 'qos' => 1],
 * ];
 * // retainHandling: 0=send retained, 1=send if new subscription, 2=don't send retained
 * $options = new SubscribeOptions(retainHandling: 1);
 * $client->subscribeWith($filters, $options);
 *
 * // MQTT 5.0 subscription with user properties
 * $filters = [
 *     ['filter' => 'data/stream', 'qos' => 2],
 * ];
 * $options = new SubscribeOptions(
 *     noLocal: true,
 *     retainAsPublished: true,
 *     properties: [
 *         'user_properties' => [
 *             'client_type' => 'monitoring',
 *             'priority' => 'high',
 *         ],
 *     ],
 * );
 * $client->subscribeWith($filters, $options);
 * ```
 *
 * SUBACK Response:
 * The broker responds with a SUBACK packet containing return codes for each subscription:
 * - MQTT 3.1.1: 0x00, 0x01, 0x02 = granted QoS 0/1/2; 0x80 = failure
 * - MQTT 5.0: 0x00, 0x01, 0x02 = granted QoS 0/1/2; 0x80+ = various failure reasons
 */
final class Subscribe
{
    /**
     * @param  non-empty-list<array{filter:string,qos:int}>  $filters  List of topic filters with requested QoS levels
     * @param  int  $packetId  Packet identifier for tracking the subscription request (1-65535)
     * @param  SubscribeOptions|null  $options  Additional subscription options (MQTT 5.0 only)
     *                                          - noLocal: bool (don't send back messages published by this client)
     *                                          - retainAsPublished: bool (forward retain flag as published)
     *                                          - retainHandling: int (0=send retained, 1=send if new, 2=don't send)
     *                                          - properties: array (user_properties, subscription_identifier)
     */
    public function __construct(
        public array $filters,
        public int $packetId,
        public ?SubscribeOptions $options = null,
    ) {
    }
}
