<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\QoS;

/**
 * Data Transfer Object for inbound MQTT PUBLISH messages.
 * Carries message metadata and payload without any behavior.
 */
final class InboundMessage
{
    /**
     * @param  array<string,mixed>|null  $properties  MQTT v5 properties if available
     */
    public function __construct(
        public string $topic,
        public string $payload,
        public QoS $qos,
        public bool $retain,
        public bool $dup,
        public ?int $packetId = null,
        public ?array $properties = null,
    ) {
    }
}
