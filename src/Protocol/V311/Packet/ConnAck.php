<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311\Packet;

/**
 * CONNACK packet model for MQTT 3.1.1 and 5.0.
 * - For MQTT 5.0, $returnCode is the Reason Code and $properties may contain a map of CONNACK properties.
 */
final class ConnAck
{
    /**
     * @param  array<string, mixed>|null  $properties  MQTT v5 CONNACK properties
     */
    public function __construct(
        public bool $sessionPresent,
        public int $returnCode, // v3 return code or v5 reason code (0 = Success)
        public ?array $properties = null,
    ) {
    }
}
