<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\Packet\ConnAck;

/**
 * Result of a successful MQTT connection attempt.
 *
 * Contains both high-level connection information and the raw CONNACK packet
 * for detailed inspection of broker capabilities and settings.
 */
final class ConnectResult
{
    public function __construct(
        public bool $sessionPresent,
        public string $protocol,          // "MQTT"
        public string $version,           // "3.1.1" | "5.0"
        public ?int $reasonCode = null,   // MQTT 5 (0 = Success)
        public ?string $assignedClientId = null, // MQTT 5 (optional)
        public ?ConnAck $connAck = null,  // Full CONNACK packet for detailed inspection
    ) {
    }
}
